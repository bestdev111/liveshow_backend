<?php

namespace App\Repositories;

use Illuminate\Http\Request;

use App\Helpers\Helper;

use Validator, Hash, Log, Setting, DB, Exception, Auth;

use App\User, App\StreamerGallery;

class StreamerGalleryRepository {

    /**
     * @method streamer_galleries_save()
     *
     * @uses To save gallery details of the streamer
     *
     * @created - Shobana Chandrasekar
     *
     * @updated - - 
     *
     * @param object $request - Model Object
     *
     * @return response of success / Failure
     */
    public static function streamer_galleries_save(Request $request) {

        try {

            DB::beginTransaction();
            
            $validator = Validator::make(
			    $request->all(), [
			    'image' => 'required',
                'gallery_description'=>'required',
			    'image.*' => 'image|mimes:jpg,jpeg,png|max:20000',
			    'user_id'=>'required|exists:users,id,is_content_creator,'.CREATOR_STATUS,
			    ],[
			        'image.*.required' => tr('upload_an_image'),
			        'image.*.mimes' => tr('image_formats'),
			        'image.*.max' => tr('max_size_of_image'),
			    ]
			);

            if($validator->fails()) {


                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages, 101);

            }

            $user = User::find($request->user_id);

            if ($user) {

                $user->gallery_description = $request->gallery_description ? $request->gallery_description : '';

                $user->save();
            }

            $updated_desc = DEFAULT_TRUE;

            if($request->hasFile('image')) {

                $updated_desc = DEFAULT_FALSE;

            	// Removed Index usage - When upload multiple image, some of the images can delete by user. So at the time we can delete from only preview not an objects of array.

            	// We will get all the inputs which is upload by user including removed image. so here we can restrict to upload those removed images

                $removed_index = $request->removed_index ? explode(',', $request->removed_index) : [];

                $added_photos_index = 0;

                // $images = [];

                // $images = count($request->image) == 1 ? $images = [$request->image] : $request->image;
                $images = $request->image;
                
                if(!is_array($images)) {

                    $model = new StreamerGallery;

                    $model->user_id = $request->user_id;

                    $model->image = Helper::upload_avatar("uploads/streamer_gallery", $images);

                    if ($model->save()) {

                        $added_photos_index += 1;

                    } else {

                        throw new Exception(tr('streamer_gallery_not_saving'));

                    }

                } else {

                    foreach($images as $key => $img) {

                        if (!in_array($key, $removed_index)) {
                        
                            $model = new StreamerGallery;

                            $model->user_id = $request->user_id;

                            $model->image = Helper::upload_avatar("uploads/streamer_gallery", $img);

                            if ($model->save()) {

                                $added_photos_index += 1;

                            } else {

                                throw new Exception(tr('streamer_gallery_not_saving'));

                            }

                        }

                    }
                }

                // If no photos present , throw an error

                if($added_photos_index <= 0) {

                    throw new Exception(tr('no_photos_found'));

                }
              
            }

            DB::commit();

            return response()->json(['success'=>true, 'message'=>$updated_desc ? tr('description_update_success') : tr('streamer_gallery_success')]);

        } catch(Exception $e) {

        	$response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];


            return response()->json($response_array);

        }

    }

    /**
     * @method streamer_galleries_delete()
     *
     * @uses To delete particular image based on id
     *
     * @created - Shobana Chandrasekar
     *
     * @updated - - 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public static function streamer_galleries_delete(Request $request) {

    	try {


	        $validator = Validator::make( $request->all(), [
	                'user_id'=>'required|exists:users,id',
	                'gallery_id' => 'required|exists:streamer_galleries,id,user_id,'.$request->user_id
	            ],[
	        		'gallery_id.exists'=>tr('streamer_gallery_not_exists'),
	        		'user_id.exists'=>tr('user_not_found')
	        	]
	        );

	        
	        if($validator->fails()) {

	            $error_messages = implode(',', $validator->messages()->all());

	            throw new Exception($error_messages, 101);
	            
	        } else {

	            $model = StreamerGallery::find($request->gallery_id);

	            if($model->delete()) {


	            } else {

	            	throw new Exception(tr('streamer_gallery_not_delete'));
	            	
	            }
	        }

	        $response_array = ['success'=>true, 'message'=>tr('streamer_galleries_delete_success')];

	        return response()->json($response_array);

	    } catch(Exception $e) {

	    	$response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

	    	return response()->json($response_array);

	    }
		
    }

    /**
     * @method streamer_galleries_list()
     *
     * @uses To load galleries based on user id
     *
     * @created - Shobana Chandrasekar
     *
     * @updated - - 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public static function streamer_galleries_list(Request $request) {

    	try {

	        $validator = Validator::make( $request->all(), array(
	                'user_id'=>'required|exists:users,id',
                    'skip'=>'required|numeric',
	            ),[
	        		'user_id.exists'=>tr('user_not_found'),
	        	]
	        );
	        
	        if($validator->fails()) {

	            $error_messages = implode(',', $validator->messages()->all());

	            throw new Exception($error_messages, 101);
	            
	        } else {

	            $model = StreamerGallery::select('id as gallery_id', 'image', \DB::raw('DATE_FORMAT(created_at , "%e %b %y %r") as created_date'))
                    ->where('user_id', $request->user_id)
                    ->orderBy('created_at', 'desc')
                    ->skip($request->skip)
                    ->take(Setting::get('admin_take_count'))
                    ->get();
	           
	        }

	        $response_array = ['success'=>true, 'data'=>$model];

	        return response()->json($response_array);

	    } catch(Exception $e) {

	    	$response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

	    	return response()->json($response_array);

	    }
		
    }
}