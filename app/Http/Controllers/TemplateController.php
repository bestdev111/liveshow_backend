<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\NotificationTemplate;

use Validator;

class TemplateController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');  
    }


    public function notification_template_index() {

        $model = NotificationTemplate::paginate(10);

        return view('admin.templates.notification.index')->with('model', $model)->with('page', 'templates')->with('sub_page', 'notification_template');

    }

    public function notification_template_edit(Request $request) {

        $model = NotificationTemplate::find($request->id);

        return view('admin.templates.notification.edit')->with('model', $model)->with('page', 'templates')->with('sub_page', 'notification_template');

    }

    public function save_notification_template(Request $request) {

        $validator = Validator::make($request->all(), [              
                'subject' => 'required|max:255',
                'content' => 'required',
                'id' => 'required|exists:notification_templates,id'
            ]);

        if($validator->fails()) {

            $error_messages = implode(',',$validator->messages()->all());

            return back()->with('flash_errors', $error_messages);

        } else {

            $model = NotificationTemplate::find($request->id);

            if ($model) {

                $model->subject = $request->has('subject') ? $request->subject : $model->subject;

                $model->content = $request->has('content') ? $request->content : $model->content;

                if ($model->save()) {

                    return redirect(route('admin.templates.notification_template_view', array('id'=>$model->id)))->with('flash_success', tr('notification_update_success'));

                } else {

                    return back()->with('flash_error', tr('no_results'));

                }

            } else {

                return back()->with('flash_error', tr('no_results'));
            }
        }

    }

    public function notification_template_view(Request $request) {

        $model = NotificationTemplate::find($request->id);

        return view('admin.templates.notification.view')->with('model', $model)->with('page', 'templates')->with('sub_page', 'notification_template');

    }

    public function notification_template_credential(Request $request) {

        $model = NotificationTemplate::find($request->id);

        $model->status = $model->status ? DEFAULT_FALSE : DEFAULT_TRUE;

        if ($model->save()) {

            if ($model->status) {

                return back()->with('flash_success', tr('notification_template_enabled_success'));

            } else {

                return back()->with('flash_success', tr('notification_template_disabled_success'));
            }

        } else {

            return back()->with('flash_error', tr('no_results'));

        }

    }
}