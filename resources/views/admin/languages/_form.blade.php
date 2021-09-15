<form  action="{{Setting::get('admin_delete_control') == YES  ? '#' : route('admin.languages.save') }}" method="POST" enctype="multipart/form-data" role="form">

	<div class="box-body">

		<input type="hidden" name="language_id" value="{{ $language_details->id }}">

	    <div class="form-group">
	        <label for="folder_name">{{ tr('short_name') }}</label>
	        <input type="text" class="form-control" name="folder_name" id="folder_name" placeholder="{{ tr('example_language') }}" required maxlength="4" value="{{ old('folder_name') ?: $language_details->folder_name }}">
	    </div>

	    <div class="form-group">
	        <label for="language">{{ tr('language') }}</label>
	        <div>
	            <input type="text" class="form-control" name="language" id="language" placeholder="{{ tr('example_language2') }}" required maxlength="4" value="{{ old('language') ?: $language_details->language }}">
	        </div>
	    </div>

	    <div class="col-sm-6">
	    <div class="form-group">
	        <label for="auth_file">{{ tr('auth_file') }}</label>
	        <input type="file" id="auth_file" name="auth_file" accept=".php" placeholder="{{ tr('picture') }}">
	        <br>	        
	        <ul class="ace-thumbnails clearfix" style="list-style-type: none;">
	            <li>	                
	                <div class="tools tools-bottom">
	                    <a href="{{ route('admin.languages.download', ['folder_name' => $language_details->folder_name, 'file_name'=>'auth']) }}">
	                        <i class="fa fa-download"></i> {{tr('download_file')}}
	                    </a>
	                </div>
	            </li>
	        </ul>
	    </div>

		</div> <div class="col-sm-6">
	    <div class="form-group">
	        <label for="messages_file">{{ tr('messages_file') }}</label>
	        <input type="file" id="messages_file" name="messages_file" placeholder="{{ tr('picture') }}" accept=".php">
	         <br>
	        <ul class="ace-thumbnails clearfix" style="list-style-type: none;">
	            <li>	                
	                <div class="tools tools-bottom">
	                    <a href="{{ route('admin.languages.download', ['folder_name' => $language_details->folder_name, 'file_name'=>'messages']) }}">
	                        <i class="fa fa-download"></i> {{tr('download_file')}}
	                    </a>
	                </div>
	            </li>
	        </ul>
	    </div>
	    </div>
 		<div class="col-sm-6">
	    <div class="form-group">
	        <label for="pagination_file">{{ tr('pagination_file') }}</label>
	        <input type="file" id="pagination_file" name="pagination_file" placeholder="{{ tr('picture') }}" accept=".php">
	         <br>
	        <ul class="ace-thumbnails clearfix" style="list-style-type: none;">
	            <li>	                
	                <div class="tools tools-bottom">
	                    <a href="{{ route('admin.languages.download', ['folder_name' => $language_details->folder_name, 'file_name'=>'pagination']) }}">
	                        <i class="fa fa-download"></i> {{tr('download_file')}}
	                    </a>
	                </div>
	            </li>
	        </ul>
	    </div>
	    </div>
 		
 		<div class="col-sm-6">
	    <div class="form-group">
	        <label for="passwords_file">{{ tr('passwords_file') }}</label>
	        <input type="file" id="passwords_file" name="passwords_file" placeholder="{{ tr('picture') }}" accept=".php">
	         <br>
	        <ul class="ace-thumbnails clearfix" style="list-style-type: none;">
	            <li>
	                <div class="tools tools-bottom">
	                    <a href="{{ route('admin.languages.download', ['folder_name' => $language_details->folder_name, 'file_name' => 'passwords']) }}">
	                        <i class="fa fa-download"></i> {{tr('download_file')}}
	                    </a>
	                </div>
	            </li>
	        </ul>
	    </div>
	    </div>

	    <div class="col-sm-6">
	    <div class="form-group">
	        <label for="validation_file">{{ tr('validation_file') }}</label>
	        <input type="file" id="validation_file" name="validation_file" placeholder="{{ tr('picture') }}" accept=".php">
	         <br>
	        <ul class="ace-thumbnails clearfix" style="list-style-type: none;">
	            <li>	                
	                <div class="tools tools-bottom">
	                    <a href="{{ route('admin.languages.download', ['folder_name' => $language_details->folder_name, 'file_name'=>'validation']) }}">
	                        <i class="fa fa-download"></i> {{tr('download_file')}}
	                    </a>
	                </div>
	            </li>
	        </ul>
	    </div>
	    </div>

	</div>

	<div class="box-footer">

	    <button type="reset" class="btn btn-danger">{{ tr('cancel') }}</button>
	    
	    <button type="submit" class="btn btn-success pull-right" @if(Setting::get('admin_delete_control') == YES ) disabled  @endif>{{tr('submit')}}</button>
	   
	</div>

</form>