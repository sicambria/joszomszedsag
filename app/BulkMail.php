<?php

namespace App;

use App\Notifications\BulkEmail;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Role;

class BulkMail extends Model {
  protected $table='bulk_mails';

  protected $fillable = ['groups', 'subject', 'mail'];
  
  public function save(array $options = []) {
    $this->user_id = Auth::user()->id;
    $groups = json_decode($this->groups);
    $group_names = array();
    
    foreach ($groups as &$group) {
      $role = $group !== null ? Role::find($group) : null;
      $group_names[] = $role ? $role->display_name : (string) $group;
      $users = User::where('role_id', $group)->get();
      foreach ($users as &$user) {
        $user->notify(new BulkEmail($user, $this));
      }
    }
    saveLog(Auth::user()->id,'Küldött egy köremailt az alábbi csoportoknak: '. implode(', ', $group_names));
    parent::save();
  }
}