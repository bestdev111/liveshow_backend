<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Repositories\CommonRepository as CommonRepo;

use App\Helpers\Helper;

use App\Language;

use App\Settings;

use Exception;

use DB;

class LanguageController extends Controller
{
    
    /**
     * Function Name : languages_index()
     *
     * @uses To list out language object details
     *
     * @created Anjana H 
     *
     * @updated Anjana H
     *
     * @param
     *
     * @return View page
     */

    public function languages_index() {
        // Load Lanugages
        $languages = Language::paginate(10);

        return view('admin.languages.index')
                    ->withPage('languages')
                    ->with('sub_page','languages-view')
                    ->with('languages', $languages);
    }

    /**
     * Function Name : languages_create()
     *
     * @uses To create a language object details
     *
     * @created Anjana H 
     *
     * @updated Anjana H
     *
     * @param 
     *
     * @return View page
     */
    public function languages_create(Request $request) {

        $language_details = new Language;

        return view('admin.languages.create')
                ->withPage('languages')
                ->with('sub_page','languages-create')
                ->with('language_details', $language_details);
    }
 
    /**
     * Function Name : languages_edit()
     *
     * @uses To display and update language object details based on language id
     *
     * @created  Anjana H
     *
     * @updated Anjana H
     *
     * @param Integer (request) $language_id
     *
     * @return View page
     */
    public function languages_edit(Request $request) {

        try {

            $language_details = Language::where('id', $request->language_id)->first();

            if(!$language_details) {

                throw new Exception( tr('admin_language_not_found'), 101);
            } 

            return view('admin.languages.edit')
                    ->withPage('languages')
                    ->with('sub_page','languages-view')
                    ->with('language_details', $language_details);      

        } catch( Exception $e) {
            
            $error = $e->getMessage();

            return redirect()->route('admin.languages.index')->with('flash_error',$error);
        }
    }

    /**
     * Function Name : languages_save()
     *
     * @uses To save the language object details of new/existing based on details
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param (request) language details
     *
     * @return success/error message
     */
    public function languages_save(Request $request) {
        
        try {
            
            $language_details = CommonRepo::languages_save($request);
            
            if ($language_details['success'] == true) {
                
                return redirect(route('admin.languages.index'))->with('flash_success', $language_details['message']);            
            } 
                
            throw new Exception($language_details['error'], $language_details['code']);  

        } catch( Exception $e) {
            
            $error = $e->getMessage();

            return redirect()->back()->withInput()->with('flash_error',$error);
        }
    
    }
  
    /**
     * Function: languages_delete()
     * 
     * @uses To delete the languages object based on language id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param 
     *
     * @return success/failure message
     */
    public function languages_delete(Request $request) {

        try {
            
            DB::beginTransaction();

            $language_details = Language::where('id', $request->language_id)->first();

            if(!$language_details) {

                throw new Exception(tr('admin_language_not_found'), 101);
            }
            
            $folder_name = $language_details->folder_name;

            if ($language_details->delete()) {

                $setting_details = Settings::where('key', 'default_lang')->first();

                if($setting_details) {

                    $setting_details->value = 'en';

                    if($setting_details->save()) {
                        
                        DB::commit();

                        Helper::delete_language_files($folder_name, DEFAULT_TRUE, '');

                        return back()->with('flash_success', tr('admin_language_delete_success'));                   
                    } 
                        
                    throw new Exception(tr('admin_language_delete_error'), 101);
                }  
            
            }
            
            throw new Exception(tr('admin_language_delete_error'), 101);
                   
        } catch (Exception $e) {
            
            DB::rollback();

            $error = $e->getMessage();

            return redirect()->route('admin.languages.index')->with('flash_error',$error);
        }
    }

    /**
     * Function Name : languages_status()
     *
     * @uses To update languages status to approve/decline based on language id
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param Integer (request) $language_id
     *
     * @return success/error message
     */
    public function languages_status_change(Request $request) {

        try {

            DB::beginTransaction();
       
            $language_details = Language::find($request->language_id);

            if(!$language_details) {
                
                throw new Exception(tr('admin_language_not_found'), 101);
            }

            $language_details->status = $language_details->status == APPROVED ? DECLINED : APPROVED ;
                        
            $message = $language_details->status == APPROVED ? tr('admin_language_activate_success') : tr('admin_language_deactivate_success');

            if( $language_details->save() ) {

                DB::commit();

                return back()->with('flash_success',$message);
            } 

            throw new Exception(tr('admin_language_status_error'), 101);
           
        } catch( Exception $e) {

            DB::rollback();
            
            $error = $e->getMessage();

            return redirect()->route('admin.languages.index')->with('flash_error',$error);
        }
    }

    /**
     * Function Name : languages_download()
     *
     * @uses To download language file 
     *
     * @created Anjana H 
     *
     * @updated Anjana H
     *
     * @param (request) folder_name, file_name, 
     *
     * @return View page
     */
    public function languages_download(Request $request) {
       
        try {

            $folder_name = $request->folder_name ?: 'en';

            $file_name = $request->file_name;

            if(!$folder_name || !$file_name) {

                throw new Exception(tr('something_error'), 101);                
            }

            //PDF file is stored under project/public/download/info.pdf
          
            $file_path = base_path(). "/resources/lang/".$folder_name.'/'.$file_name.'.php';

            $headers = array(
                      'Content-Type: application/x-php',
                    );

            return response()->download($file_path , $file_name.'.php', $headers);
            
        } catch (Exception $e) {
                        
            $error = $e->getMessage();

            return redirect()->route('admin.languages.index')->with('flash_error',$error);
        }
    }

    /**
     * Function Name : set_default_language()
     *
     * @uses To set default language
     *
     * @created Anjana H 
     *
     * @updated Anjana H
     *
     * @param (request) folder_name, file_name, 
     *
     * @return View page
     */
    public function languages_set_default(Request $request) {

        try {

            // Load setting table
            DB::beginTransaction();

            $setting_details = Settings::where('key','default_lang')->first();

            if (!$setting_details) { 

                throw new Exception(tr('something_error'), 101);           
            }

            $setting_details->value = $request->language_file;

            if( $setting_details->save()) {

                DB::commit();

                $fp = fopen(base_path() .'/config/new_config.php' , 'w');

                fwrite($fp, "<?php return array( 'locale' => '".$request->language_file."', 'fallback_locale' => '".$request->language_file."');?>");
                
                fclose($fp);

                \Log::info("Key : ".config('app.locale'));

                return back()->with('flash_success' , tr('set_default_language_success'))->with('flash_language', true);
            } 

            throw new Exception(tr('something_error'), 101);   
            

        } catch (Exception $e) {
            
            DB::rollback();
            
            $error = $e->getMessage();

            return redirect()->route('admin.languages.index')->with('flash_error',$error);
        }

    }
}
