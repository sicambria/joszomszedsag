<?php
namespace App\Http\Controllers;
use App\Address;
use App\AddressType;
use App\HelpType;
use App\Notifications\RequestAccept;
use App\Notifications\RequestDrop;
use App\Notifications\StatusChange;
use App\Notifications\SupportRequest;
use App\Notifications\VolunteerTextNotification;
use App\User;
use App\UserSupportData;
use App\UserVolunteerData;
use App\Support\RoleIds;
use Barryvdh\Debugbar\LaravelDebugbar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpParser\Node\Expr\Cast\Object_;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Voyager;

class DashboardController extends \App\Http\Controllers\Controller {
  public $user_role;
  public $current_user;
  public function __construct() {
    $this->middleware('auth');
  }
  public function index() {
    return view('dashboard.view');
  }
  
  public function new_help_request() {
    return view('dashboard.help_request.new_request');
  }

  public function open_help_request_list() {
    $own_addresses = DB::select(
      'SELECT a.*
      FROM `addresses` a
      LEFT JOIN `user_data_volunteer_addresses` udva ON a.`id` = udva.`address_id`
      RIGHT JOIN `user_data_volunteer` udv ON udva.`user_data_volunteer_id` = udv.`id`
      WHERE udv.`user_id` = ?',
      [Auth::id()]
    );

    $visible_addresses = $this->getSupportRequestAddresses($own_addresses);

    return view('dashboard.help_request.open_request')
      ->with(array(
        'help_requests' => $visible_addresses,
        'own_addresses' => $own_addresses,
        'ajaxPostRoute' => route('volunteer_for_support_ajax')
      ));
  }

  public function volunteer_for_support_ajax(Request $request)
  {
    $validated = $this->validate($request, [
      'id' => 'required|integer|exists:user_data_support,id',
      'status' => 'required|integer|in:0,1,3',
    ]);
    $userSupportData = UserSupportData::findOrFail($validated['id']);

    $currentUserId = Auth::id();
    $isAdmin = Auth::user()->role_id === RoleIds::ADMIN;
    $isOwner = (int) $userSupportData->user_id === (int) $currentUserId;
    $isAssignedVolunteer = $userSupportData->volunteer_user_id !== null
      && (int) $userSupportData->volunteer_user_id === (int) $currentUserId;

    // Taking a task (status 3) is open to any verified volunteer; every other
    // transition requires owner, assigned volunteer, or admin.
    if ((int) $validated['status'] !== 3 && !$isOwner && !$isAssignedVolunteer && !$isAdmin) {
      abort(403);
    }

    $previous_status = $userSupportData->status;
    if($validated['status'] == 0) {
      $userSupportData->volunteer_user_id = null;
    } else if($validated['status'] == 3) {

      if($userSupportData->status == 3) {
        return response()->json(['error' => true], 422);
      }

      $userSupportData->volunteer_user_id = $currentUserId;
    }

    $userSupportData->status = $validated['status'];

    if ($validated['status'] == 1) {
      $userSupportData->status = 1;
    }

    $userSupportData->save();

    $userData = User::find($userSupportData->user_id);

    $volunteer_user = $userSupportData->volunteer_user_id?User::find($userSupportData->volunteer_user_id):Auth::user();

    if($validated['status'] !== $previous_status) {
        //Support user
      if ($userData) {
        $userData->notify(new StatusChange($userData, $userSupportData->status, $previous_status, 'support'));
      }

      //Volunteer user
      if ($volunteer_user) {
        $volunteer_user->notify(new StatusChange($volunteer_user, $userSupportData->status, $previous_status, 'volunteer'));
      }

    }

    $returnObj = new \stdClass();

    // PII is only disclosed to entitled parties (owner / assigned / admin).
    if (!$isOwner && !$isAssignedVolunteer && !$isAdmin && (int) $validated['status'] === 3) {
      $isAssignedVolunteer = true;
    }
    if ($isOwner || $isAssignedVolunteer || $isAdmin) {
      $returnObj->name = $userData ? $userData->first_name.' '.$userData->last_name : '';
      $returnObj->email = $userData ? $userData->email : '';
      $returnObj->phone = $userData ? $userData->phone_number : '';
      $returnObj->facebook_profile = $userData ? $userData->facebook_profile : '';
    } else {
      abort(403);
    }

    return response()->json($returnObj);
  }

  public function get_more_user_details_ajax(Request $request)
  {
    $validated = $this->validate($request, [
      'id' => 'required|integer',
      'type' => 'required|integer|in:6,7',
    ]);
    $returnObj = new \stdClass();

    $currentUserId = Auth::id();
    $isAdmin = Auth::user()->role_id === RoleIds::ADMIN;

    if($validated['type'] == RoleIds::REQUESTER) {
      $userSupportData = UserSupportData::findOrFail($validated['id']);
      $user_id = $userSupportData->user_id;
      $isOwner = (int) $userSupportData->user_id === (int) $currentUserId;
      $isAssignedVolunteer = $userSupportData->volunteer_user_id !== null
        && (int) $userSupportData->volunteer_user_id === (int) $currentUserId;
      if (!$isOwner && !$isAssignedVolunteer && !$isAdmin) {
        abort(403);
      }
      $returnObj->description = $userSupportData->description;
    } else if ($validated['type'] == RoleIds::VOLUNTEER) {
      $user_id = $validated['id'];
      // Volunteer directory entries only expose contact to admins or to the
      // requester in the same postcode area; otherwise require admin.
      if (!$isAdmin && (int) $user_id !== (int) $currentUserId) {
        // Keep permissive listing but hide PII for non-admins (see below).
      }
    } else {
      return response()->json(['error' => true], 422);
    }

    $userData = User::findOrFail($user_id);

    if ($validated['type'] == RoleIds::VOLUNTEER && !$isAdmin && (int) $user_id !== (int) $currentUserId) {
      $returnObj->name = $userData->display_name;
      $returnObj->email = '';
      $returnObj->phone = '';
      $returnObj->facebook_profile = '';
    } else {
      $returnObj->name = $userData->first_name.' '.$userData->last_name;
      $returnObj->email = $userData->email;
      $returnObj->phone = $userData->phone_number;
      $returnObj->facebook_profile = $userData->facebook_profile;
    }

    return response()->json($returnObj);
  }

  public function own_help_request_list()
  {

    return view('dashboard.help_request.own_request')
      ->with(array(
        'help_requests' => $this->getOwnHelpRequests(),
        'ajaxPostRoute' => route('volunteer_for_support_ajax'),
        'ajaxMoreInfoRoute' => route('get_more_user_details_ajax')
      ));
  }

  public function my_help_request_list()
  {

    return view('dashboard.help_request.my_requests')
      ->with(array(
        'help_requests' => $this->getMyHelpRequests(),
        'ajaxPostRoute' => route('volunteer_for_support_ajax'),
        'ajaxMoreInfoRoute' => route('get_more_user_details_ajax')
      ));
  }

  public function volunteers_list()
  {

    return view('dashboard.volunteers_list')
      ->with(array(
        'volunteers_list' => $this->getVolunteers()
      ));
  }

  public function send_volunteer_notification_ajax(Request $request)
  {
    $validated = $this->validate($request, [
      'volunteer_id' => 'nullable|integer|exists:user_data_volunteer,id',
      'user_id' => 'nullable|integer|exists:users,id',
      'text' => 'required|string|max:1000',
    ]);
    if(isset($validated['volunteer_id'])) {
      $volunteerData = UserVolunteerData::findOrFail($validated['volunteer_id']);
      // Only the volunteer themselves or an admin may be impersonated as sender target.
      if ((int) $volunteerData->user_id !== (int) Auth::id() && Auth::user()->role_id !== RoleIds::ADMIN) {
        abort(403);
      }
      $userData = User::findOrFail($volunteerData->user_id);
    } else if (isset($validated['user_id'])) {
      $userData = User::findOrFail($validated['user_id']);
    } else {
      return response()->json(['error' => true], 422);
    }

    $userData->notify(new VolunteerTextNotification(Auth::user(), $userData, strip_tags($validated['text'])));

    return response()->json(1);
  }

  public function profile() {
    return view('dashboard.profile');
  }

  public function save_profile(Request $request)
  {

    $this->validate($request,[
      'display_name' => 'required|regex:/^[\pL\pM\pN\s\.\-]+$/u',
      'email' => 'required|email|unique:users,email,'.Auth::user()->id,
      'phone' => 'required|string',
      'postcode' => 'required|digits:4|exists:available_post_codes,post_code',
      'city' => 'required|alpha|exists:cities,name',
      'street' => 'required|regex:/^[\pL\pM\pN\s]+$/u',
      'house_number' => 'required|string',
      'facebook_profile' => 'nullable|url',
    ]);

    if(isset($request->availability)) {
      $this->validate($request,[
        'has_car' => 'sometimes|accepted',
        'availability' => 'required|string|max:50',
        'area_postcode' => 'required|array|min:1',
        'area_city' => 'required|array|min:1',
        'area_street' => 'required|array|min:1',
        'area_from-house-number' => 'array',
        'area_to-house-number' => 'array',
        'help_types' => 'required|array|min:1',
        'volunteer_status_cancelled' => 'sometimes|accepted',
      ]);
    }

    if(Auth::user()->role_id === RoleIds::REQUESTER) {
      $this->validate($request,[
        'privacy_policy' => 'accepted',
        'health_checkbox' => 'accepted',
      ]);
    }


    if(UserVolunteerData::where('user_id', Auth::user()->id)->first() || (Auth::user()->role_id === RoleIds::REQUESTER && $request->privacy_policy && $request->health_checkbox)) {
      $volunteer_data = $this->saveVolunteerData($request);
      $this->saveVolunteerAddress($request, $volunteer_data->id);
    }

    $this->saveUserBasic($request);

    if(isset($volunteer_data) && $volunteer_data->status == 0) {
      $volunteerSupports = UserSupportData::where('volunteer_user_id', $volunteer_data->id)->get();

      foreach ($volunteerSupports as $support) {
        $oldStatus = $support->status;

        $support->status = 0;
        $support->volunteer_user_id = null;

        $support->save();

        $userData = User::find($support->user_id);

        //Support user
        $userData->notify(new StatusChange($userData, $support->status, $oldStatus, 'support'));
        //Volunteer user
        $volunteer_user = Auth::user();
        $volunteer_user->notify(new StatusChange($volunteer_user, $support->status, $oldStatus, 'volunteer'));
      }
    }

    return redirect(route('admin_profile'));
  }

  public function save_support_data(Request $request)
  {

    $this->validate($request,[
      'facebook_profile' => 'nullable|url',
      'has_corona' => 'sometimes|accepted',
      'in_quarantine' => 'sometimes|accepted',
      'has_chronic' => 'sometimes|accepted',
      'is_healthy' => 'required_without_all:has_corona,in_quarantine,has_chronic',
      'help_types' => 'required|numeric|min:1',
      'privacy_policy' => 'accepted',
      'situation_desc' => 'nullable|string|max:255'
    ]);

    if(isset($request->postcode)) {
      $this->validate($request,[
        'postcode' => 'required|digits:4|exists:available_post_codes,post_code',
        'city' => 'required|alpha|exists:cities,name',
        'street' => 'required|regex:/^[\pL\pM\pN\s]+$/u',
        'house_number' => 'required|string',
      ]);
    }

    $this->saveSupportData($request);

    return redirect(route('admin_open_help_request'));
  }

  private function getOwnHelpRequests() {
    $help_requests = new \stdClass();
    $userId = Auth::id();

    $help_requests->incomplete = DB::select(
      'SELECT
              uds.`id` as "uds_id",
              uds.`status` as "uds_status",
              uds.`created_at` as "uds_created_at",
              uds.`help_request` as "uds_help_request",
              ht.`name` as "uds_help_request_name",
              uds.`is_healthy` as "uds_is_healthy",
              uds.`description` as "uds_description",
              uds.`updated_at` as "uds_updated_at",
              u.`display_name` as "u_display_name",
              a.*
       FROM `user_data_support` uds
       LEFT JOIN `addresses` a ON a.`id` = uds.`address_id`
       LEFT JOIN `help_types` ht ON uds.`help_request` = ht.`id`
       LEFT JOIN `users` u ON u.`id` = uds.`user_id`
       WHERE uds.`status` IN (3,4,5) AND uds.`volunteer_user_id` = ?',
      [$userId]
    );

    $help_requests->complete = DB::select(
      'SELECT
              uds.`id` as "uds_id",
              uds.`status` as "uds_status",
              uds.`created_at` as "uds_created_at",
              uds.`help_request` as "uds_help_request",
              ht.`name` as "uds_help_request_name",
              uds.`is_healthy` as "uds_is_healthy",
              uds.`description` as "uds_description",
              uds.`updated_at` as "uds_updated_at",
              u.`display_name` as "u_display_name",
              a.*
       FROM `user_data_support` uds
       LEFT JOIN `addresses` a ON a.`id` = uds.`address_id`
       LEFT JOIN `help_types` ht ON uds.`help_request` = ht.`id`
       LEFT JOIN `users` u ON u.`id` = uds.`user_id`
       WHERE uds.`status` = 1 AND uds.`volunteer_user_id` = ?',
      [$userId]
    );

    return $help_requests;
  }

  private function getMyHelpRequests() {
    $help_requests = new \stdClass();
    $userId = Auth::id();

    $help_requests->incomplete = DB::select(
      'SELECT
              uds.`id` as "uds_id",
              uds.`status` as "uds_status",
              uds.`created_at` as "uds_created_at",
              uds.`help_request` as "uds_help_request",
              ht.`name` as "uds_help_request_name",
              uds.`updated_at` as "uds_updated_at",
              uds.`volunteer_user_id` as "uds_volunteer_user_id",
              u.`display_name` as "u_display_name",
              a.*
       FROM `user_data_support` uds
       LEFT JOIN `addresses` a ON a.`id` = uds.`address_id`
       LEFT JOIN `help_types` ht ON uds.`help_request` = ht.`id`
       LEFT JOIN `users` u ON u.`id` = uds.`volunteer_user_id`
       WHERE uds.`status` IN (0, 2, 3, 4, 5)
       AND uds.`user_id` = ?',
      [$userId]
    );

    $help_requests->complete = DB::select(
      'SELECT
              uds.`id` as "uds_id",
              uds.`status` as "uds_status",
              uds.`created_at` as "uds_created_at",
              uds.`help_request` as "uds_help_request",
              ht.`name` as "uds_help_request_name",
              uds.`description` as "uds_description",
              uds.`updated_at` as "uds_updated_at",
              u.`first_name` as "volunteer_first_name",
              u.`last_name` as "volunteer_last_name",
              u.`display_name` as "volunteer_display_name",
              a.*
       FROM `user_data_support` uds
       LEFT JOIN `addresses` a ON a.`id` = uds.`address_id`
       LEFT JOIN `help_types` ht ON uds.`help_request` = ht.`id`
       LEFT JOIN `users` u ON u.`id` = uds.`volunteer_user_id`
       WHERE uds.`status` = 1
       AND uds.`user_id` = ?',
      [$userId]
    );

    return $help_requests;
  }
  
  private function getSupportRequestAddresses($volunteer_address) {
    $volunteer_post_codes = array();

    foreach ($volunteer_address as $addr) {
      $volunteer_post_codes[] = (int) $addr->post_code;
    }

    $volunteer_streets = array();

    foreach ($volunteer_address as $addr) {
      $volunteer_streets[$addr->street] = array(
        'from' => isset($addr->house_number)?$addr->house_number:null,
        'to' => isset($addr->house_number_2)?$addr->house_number_2:null
      );
    }

    $baseSql = 'SELECT
             uds.`id` as "uds_id",
             uds.`status` as "uds_status",
             uds.`created_at` as "uds_created_at",
             uds.`help_request` as "uds_help_request",
             ht.`name` as "uds_help_request_name",
             uds.`is_healthy` as "uds_is_healthy",
             uds.`description` as "uds_description",
             a.*,
             u.`display_name` as "u_display_name"
      FROM `user_data_support` uds
      LEFT JOIN `addresses` a ON a.`id` = uds.`address_id`
      LEFT JOIN `help_types` ht ON uds.`help_request` = ht.`id`
      RIGHT JOIN `users` u ON uds.`user_id` = u.`id`
      WHERE (uds.`status` = 0 OR uds.`status` = 2)';
    if (Auth::user()->role_id !== RoleIds::ADMIN) {
      if (empty($volunteer_post_codes)) {
        return [];
      }
      $placeholders = implode(',', array_fill(0, count($volunteer_post_codes), '?'));
      $postcode_addresses = DB::select($baseSql.' AND a.`post_code` IN ('.$placeholders.')', $volunteer_post_codes);
    } else {
      $postcode_addresses = DB::select($baseSql);
    }

    $formattedNow = null;
    $formattedCreation = null;
    foreach($postcode_addresses as $key => &$address) {
      if($address->uds_status == 2) {
        $now = new \DateTime();
        $formattedNow = $now->format('H:i');

        $creation = new \DateTime($address->uds_created_at);
        $creation->modify('+3 hour');
        $formattedCreation = $creation->format('H:i');

      }

      if(in_array($address->street, array_keys($volunteer_streets))) {
        if($volunteer_streets[$address->street]['from'] && $volunteer_streets[$address->street]['to']
          && $address->house_number > $volunteer_streets[$address->street]['from']
          && $address->house_number < $volunteer_streets[$address->street]['to']) {
          $priority = 0;
        } else {
          $priority = 1;
        }
      } else {
        $priority = 2;

        if(isset($formattedCreation) && isset($formattedNow) && ($formattedCreation > $formattedNow)) {
          unset($postcode_addresses[$key]);
          continue;
        }
      }
      $address->priority = $priority;
    }

    return $postcode_addresses;
  }

  public function getVolunteers()
  {
    $user_address = Auth::user()->home_address_id ? Address::find(Auth::user()->home_address_id) : null;

    $help_types = $this->getHelpTypes();

    $baseSql = 'SELECT
        a.*,
        udv.`helping_groups`,
        udv.`availability`,
        udv.`id` as "volunteer_id",
        udv.`has_car`,
        u.`display_name`
      FROM `user_data_volunteer` udv
      LEFT JOIN `user_data_volunteer_addresses` udva ON udva.`user_data_volunteer_id` = udv.`id`
      RIGHT JOIN `addresses` a ON a.`id` = udva.`address_id`
      RIGHT JOIN `users` u ON udv.`user_id` = u.`id`
      WHERE udv.status = 1';
    if (Auth::user()->role_id !== RoleIds::ADMIN && $user_address) {
      $volunteer_data = DB::select($baseSql.' AND a.`post_code` = ?', [(int) $user_address->post_code]);
    } else {
      $volunteer_data = DB::select($baseSql);
    }

    foreach ($volunteer_data as &$data) {
      $parsedHelps = json_decode($data->helping_groups ? $data->helping_groups : '[]', true);
      if (!is_array($parsedHelps)) {
        $parsedHelps = [];
      }

      $data->helping_groups = array();

      foreach($parsedHelps as $help) {
        if (isset($help_types[$help])) {
          $data->helping_groups[] = $help_types[$help];
        }
      }
    }

    return $volunteer_data;
  }

  public function saveUserBasic(Request $request)
  {
    $user = User::findOrFail(Auth::id());
    $user->email = $request->email;
    $user->display_name = $request->display_name;
    $user->facebook_profile = $request->facebook_profile?:'';
    $user->phone_number = $request->phone;
    $user->home_address_id = $this->saveHomeAddress($request)->id;

    if($user->role_id == RoleIds::REQUESTER && UserVolunteerData::where('user_id', Auth::id())->first()) {
      $user->role_id = RoleIds::VOLUNTEER;
    }

    if($user->save()) {
      return $user;
    } else {
      return false;
    }
  }

  public function saveHomeAddress(Request $request)
  {
    return Address::firstOrCreate([
      'post_code' => $request->postcode,
      'city' => $request->city,
      'street' => $request->street,
      'house_number' => isset($request->house_number)?trim($request->house_number, ' ,.-*_:/'):null,
      'house_number_2' => null
    ]);
  }

  public function saveVolunteerAddress(Request $request, $volunteer_data_id)
  {
    $res = true;

    $address_ids = array();

    $volunteer_data_id = (int) $volunteer_data_id;
    $beforeRow = DB::select(
      'SELECT COUNT(`address_id`) as count
      FROM `user_data_volunteer_addresses`
      WHERE `user_data_volunteer_id` = ?',
      [$volunteer_data_id]
    );
    $beforeIdCount = $beforeRow ? (int) $beforeRow[0]->count : 0;

    $areaStreets = is_array($request->input('area_street')) ? $request->input('area_street') : [];
    $areaPostcodes = is_array($request->input('area_postcode')) ? $request->input('area_postcode') : [];
    $areaCities = is_array($request->input('area_city')) ? $request->input('area_city') : [];
    $areaFrom = is_array($request->input('area_from-house-number')) ? $request->input('area_from-house-number') : [];
    $areaTo = is_array($request->input('area_to-house-number')) ? $request->input('area_to-house-number') : [];

    for($i = 0; $i < count($areaStreets); $i++) {
      if(!empty($areaStreets[$i])) {
        $address = Address::firstOrCreate([
          'post_code' => isset($areaPostcodes[$i]) ? $areaPostcodes[$i] : null,
          'city' => isset($areaCities[$i]) ? $areaCities[$i] : null,
          'street' => $areaStreets[$i],
          'house_number' => isset($areaFrom[$i])?trim($areaFrom[$i],' ,.-*_;:/'):null,
          'house_number_2' => isset($areaTo[$i])?trim($areaTo[$i], ' ,.-*_:/'):null,
        ]);
        $address_ids [] = $address->id;

        $res &= DB::table('user_data_volunteer_addresses')->insertOrIgnore([
          ['user_data_volunteer_id' => $volunteer_data_id, 'address_id' => (int) $address->id],
        ]) !== false;
      }
    }

    // Delete not used address relations
    $helper_addresses = DB::select(
      'SELECT `address_id`
      FROM `user_data_volunteer_addresses`
      WHERE `user_data_volunteer_id` = ?',
      [$volunteer_data_id]
    );

    $afterIdCount = count($helper_addresses);

    $delCounter = 0;
    foreach ($helper_addresses as $ha) {
      if(!in_array($ha->address_id, $address_ids)) {
        $res &= DB::delete(
          'DELETE FROM `user_data_volunteer_addresses`
          WHERE `user_data_volunteer_id` = ? AND `address_id` = ?',
          [$volunteer_data_id, (int) $ha->address_id]
        ) !== false;
        $delCounter++;
      }
    }

    if(($beforeIdCount < $afterIdCount) || $delCounter ) {
      saveLog(Auth::user()->id, 'Hozzáadott '.($afterIdCount-$beforeIdCount).'db önkéntes címet és törölt '.$delCounter.'db-t.');
    }

    return $res;
  }

  public function saveVolunteerData(Request $request) {
    return UserVolunteerData::updateOrCreate([
      'user_id' => Auth::user()->id,
    ],
    [
      'has_car' => (isset($request->has_car)&&$request->has_car=='on')?1:0,
      'availability' => $request->availability,
      'helping_groups' => json_encode($request->help_types),
      'status' => isset($request->volunteer_status_cancelled) && $request->volunteer_status_cancelled=='on'?0:1
    ]);
  }

  public function saveSupportData(Request $request) {
    $userData = new UserSupportData;

    $userData->user_id = Auth::id();
    $userData->help_request = $request->help_types;
    $userData->has_coronavirus = isset($request->has_corona) && $request->has_corona=='on' ? 1 : 0;
    $userData->is_in_quarantine = isset($request->in_quarantine) && $request->in_quarantine=='on' ? 1 : 0;
    $userData->has_chronic_illness = isset($request->has_chronic) && $request->has_chronic=='on' ? 1 : 0;
    $userData->is_healthy = isset($request->is_healthy) && $request->is_healthy=='on' ? 1 : 0;
    $userData->description = isset($request->situation_desc) ? mb_substr(strip_tags($request->situation_desc), 0, 2000) : null;
    $userData->volunteer_user_id = null;
    $userData->status = 2;

    if(isset($request->street)) {
      $address = $this->saveHomeAddress($request);
      $userData->address_id = $address->id;
    } else {
      $userData->address_id = Auth::user()->home_address_id;
    }

    $volunteer_ids = $this->getVolunteersForNewSupportRequest(Address::find($userData->address_id));
    foreach ($volunteer_ids as &$volunteer_id) {
      $volunteer = User::find($volunteer_id);
      if($volunteer) {
        $volunteer->notify(new SupportRequest($volunteer));
      }
    }

    if(count($volunteer_ids) < 1) {
      $userData->status = 0;
    }

    return $userData->save();
  }

  public function getVolunteersForNewSupportRequest($address)
  {
    if (!$address) {
      return [];
    }
    $user_address = $address;

    $volunteer_data = DB::select(
      'SELECT
        udv.`user_id`,
        a.*
      FROM `user_data_volunteer` udv
      LEFT JOIN `user_data_volunteer_addresses` udva ON udva.`user_data_volunteer_id` = udv.`id`
      RIGHT JOIN `addresses` a ON a.`id` = udva.`address_id`
      WHERE a.`post_code` = ? AND a.`street` = ?',
      [$user_address->post_code, $user_address->street]
    );
    $volunteer_ids = array();
    foreach ($volunteer_data as $vd) {
      if($vd->house_number && $vd->house_number_2) {
        if(($user_address->house_number >= $vd->house_number && $user_address->house_number <= $vd->house_number_2 )) {
          $volunteer_ids[] = $vd->user_id;
        }
      }
      else {
        $volunteer_ids[] = $vd->user_id;
      }
    }

    return $volunteer_ids;
  }

  public function getHelpTypes() {
    $returnArr = array();

    $helpTypes = HelpType::all();

    foreach ($helpTypes as $helpType) {
      $returnArr[$helpType->id] = $helpType->name;
    }

    return $returnArr;
  }
}