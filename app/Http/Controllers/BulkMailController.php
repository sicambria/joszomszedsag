<?php

namespace App\Http\Controllers;

use App\BulkMail;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bespoke replacement for the Voyager bulk_mails BREAD removed in MOD-02.
 * Admin-only (see routes); sending notifies every user in the chosen roles.
 */
class BulkMailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorizeAdmin();

        return view('dashboard.bulk_mail.index')->with([
            'mails' => BulkMail::orderByDesc('id')->paginate(20),
        ]);
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('dashboard.bulk_mail.create')->with([
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $this->validate($request, [
            'groups' => 'required|array|min:1',
            'groups.*' => 'integer|exists:roles,id',
            'subject' => 'required|string|max:255',
            'mail' => 'required|string|max:10000',
        ]);

        $bulkMail = new BulkMail();
        $bulkMail->groups = json_encode(array_map('intval', $validated['groups']));
        $bulkMail->subject = strip_tags($validated['subject']);
        $bulkMail->mail = $validated['mail'];
        $bulkMail->save();

        return redirect()
            ->route('bulk_mail_index')
            ->with(['message' => 'Körlevél elküldve.', 'alert-type' => 'success']);
    }

    private function authorizeAdmin(): void
    {
        if (! Auth::user() || ! Auth::user()->isAdmin()) {
            abort(403);
        }
    }
}
