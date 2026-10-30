<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\EmailNotificationSetting;
use App\Models\PusherSetting;
use App\Models\PushNotificationSetting;
use App\Models\SlackSetting;
use App\Models\SmtpSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;


class NotificationSettingController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.notificationSettings';
        $this->activeSettingMenu = 'notification_settings';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('manage_notification_setting') !== 'all');

            return $next($request);
        });
    }

    public function index()
    {
        $tab = request('tab');

        $this->emailSettings = EmailNotificationSetting::all();
        $this->slackSettings = SlackSetting::first();
        $this->pushSettings = PushNotificationSetting::first();
        $this->pusherSettings = PusherSetting::first();

        switch ($tab) {
            case 'email-template-message':
                $this->checkedAll = $this->emailSettings->count() == $this->emailSettings->filter(function ($value) {
                    return $value->send_slack == 'yes';
                })->count();

                $this->view = 'notification-settings.ajax.email-template-message';
                break;

        case 'slack-setting':
            $this->checkedAll = $this->emailSettings->count() == $this->emailSettings->filter(function ($value) {
                    return $value->send_slack == 'yes';
            })->count();

            $this->view = 'notification-settings.ajax.slack-setting';
            break;

        case 'push-notification-setting':
            $this->checkedAll = $this->emailSettings->count() == $this->emailSettings->filter(function ($value) {
                    return $value->send_push == 'yes';
            })->count();

            $this->view = 'notification-settings.ajax.push-notification-setting';
            break;

        case 'pusher-setting':
            $this->view = 'notification-settings.ajax.pusher-setting';
            break;

        default:
            $this->checkedAll = $this->emailSettings->count() == $this->emailSettings->filter(function ($value) {
                    return $value->send_email == 'yes';
            })->count();

            $this->smtpSetting = SmtpSetting::first();

            try {
                $this->smtpSetting->mail_password;
            }catch (DecryptException $e){
                // when we get message like below set password as null or o
                // The MAC is invalid.
                // The payload is invalid.
                $this->smtpSetting->mail_password = null;
                $this->smtpSetting->save();
            }
            $this->view = 'notification-settings.ajax.email-setting';
            break;
        }

        $this->activeTab = $tab ?: 'email-setting';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle, 'activeTab' => $this->activeTab]);
        }

        return view('notification-settings.index', $this->data);
    }
    
      public function get_emailtemplate_list()
    {
        $templates = DB::table('email_templates')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $templates,
        ]);
    }
    public function emailtemplate_edit($id)
    {
        $template = DB::table('email_templates')->where('id', $id)->first();

        if (! $template) {
            return redirect()->back()->with('error', 'Email template not found.');
        }
      $this->data['t_variable'] = DB::table('email_templates_variable')->where('email_templates_id', $id)->get();

        $this->data['template'] = $template;
        return view('notification-settings.email_template_edit', $this->data);
    }

public function emailtemplate_update(Request $request)
{
    // Get the raw JSON content
    $json = $request->getContent();
    $data = json_decode($json, true);
     
    \Log::info('Raw JSON input:', $data);
     
    DB::table('email_templates')
        ->where('id', $data['id'])
        ->update([
            'name'        => $data['template_name'],
            'subject'     => $data['email_subject'],
            'template'    => $data['template'], // This now contains the HTML
            'mail_status' => $data['template_status'] == 'on' ? '1' : '0',
            'updated_at'  => now(),
        ]);
    
    return response()->json(['success' => 'Template updated successfully.']);
}

}
