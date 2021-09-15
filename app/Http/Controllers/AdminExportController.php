<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Exception, Setting, DB;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\UsersExport, App\Exports\LiveVideosExport, App\Exports\SubscriptionsExport, App\Exports\PayperviewExport, App\Exports\VodPaymentsExport, App\Exports\VodVideosExport,App\Exports\AutomaticSubscribersExport, App\Exports\CancelledSubscribersExport;

class AdminExportController extends Controller
{
    //
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');  
    }
    
    /**
	 * Function Name: users_export()
	 *
	 * @uses used export the users details into the selected format
	 *
	 * @created Maheswari
	 *
	 * @edited Maheswari
	 *
	 * @param string format (xls, csv or pdf)
	 *
	 * @return redirect users page with success or error message 
	 */
    public function users_export(Request $request) {

    	try {

    		$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new UsersExport, $filename);

		} catch(\Exception $e) {

            $error = $e->getMessage();

            return redirect()->route('admin.users.index')->with('flash_error' , $error);

        }

    }

     /**
	 * Function Name: livevideos_export()
	 *
	 * @uses used export the video details into the selected format
	 *
	 * @created Maheswari
	 *
	 * @edited Maheswari
	 *
	 * @param string format (xls, csv or pdf)
	 *
	 * @return redirect users page with success or error message 
	 */
    public function livevideos_export(Request $request) {

    	try {

    		$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new LiveVideosExport, $filename);

		} catch(\Exception $e) {

            $error = $e->getMessage();

            return redirect()->route('admin.videos.videos_list')->with('flash_error' , $error);

        }

    }

   	/**
	 * Function Name: subscriptions_export()
	 *
	 * @uses used export the subscription details into the selected format
	 *
	 * @created Maheswari
	 *
	 * @edited Maheswari
	 *
	 * @param string format (xls, csv or pdf)
	 *
	 * @return redirect users page with success or error message 
	 */
    public function subscriptions_export(Request $request) {

    	try {

    		$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new SubscriptionsExport, $filename);

		} catch(\Exception $e) {

            $error = $e->getMessage();

            return redirect()->route('admin.payments.user-payments')->with('flash_error' , $error);

        }

    }


    /**
	 * Function Name: payperview_export()
	 *
	 * @uses used export the payperview payments details into the selected format
	 *
	 * @created Maheswari
	 *
	 * @edited Maheswari
	 *
	 * @param string format (xls, csv or pdf)
	 *
	 * @return redirect users page with success or error message 
	 */
    public function payperview_export(Request $request) {

    	try {

    		$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new PayPerViewExport, $filename);

		} catch(\Exception $e) {

            $error = $e->getMessage();

            return redirect()->route('admin.payments.video-payments')->with('flash_error' , $error);

        }


    }

    /**
	 * Function Name: vod_payments_export()
	 *
	 * @uses used export the vod payments details into the selected format
	 *
	 * @created Maheswari
	 *
	 * @edited Maheswari
	 *
	 * @param string format (xls, csv or pdf)
	 *
	 * @return redirect users page with success or error message 
	 */
    public function vod_payments_export(Request $request) {

    	try {

    		$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new VodPaymentsExport, $filename);

		} catch(\Exception $e) {

            $error = $e->getMessage();

            return redirect()->route('admin.vod-videos.payments.list')->with('flash_error' , $error);

        }
    }

     /**
	 * Function Name: vod_videos_export()
	 *
	 * @uses used export the vod videos details into the selected format
	 *
	 * @created Maheswari
	 *
	 * @edited Maheswari
	 *
	 * @param string format (xls, csv or pdf)
	 *
	 * @return redirect users page with success or error message 
	 */
    public function vod_videos_export(Request $request) {

    	try {

    		$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new VodVideosExport, $filename);

		} catch(\Exception $e) {

            $error = $e->getMessage();

            return redirect()->route('admin.vod-videos.index')->with('flash_error' , $error);

        }
	}
	

    public function automatic_subscribers_export(Request $request) {

    	try {

    		$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new AutomaticSubscribersExport, $filename);

		} catch(\Exception $e) {

			$error = $e->getMessage();
			
			return redirect()->route('admin.automatic.subscribers')->with('flash_error' , $error);

		}
    }



	public function cancelled_subscribers_export(Request $request) {

		try {

			$formats = [ 'xlsx' => '.xlsx', 'csv' => '.csv', 'xls' => '.xls', 'pdf' => '.pdf'];

        	$file_format = isset($formats[$request->format]) ? $formats[$request->format] : '.xlsx';

        	$filename = routefreestring(Setting::get('site_name'))."-".date('Y-m-d-h-i-s')."-".uniqid().$file_format;

        	return Excel::download(new CancelledSubscribersExport, $filename);
	
		} catch(\Exception $e) {
			
			return redirect()->route('admin.cancelled.subscribers')->with('flash_error' , $e->getMessage());

		}		

	}




}
