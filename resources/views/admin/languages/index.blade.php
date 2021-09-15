@extends('layouts.admin')

@section('title', tr('view_languages'))

@section('content-header', tr('languages'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.languages.index')}}"><i class="fa fa-globe"></i> {{tr('languages')}}</a></li>
    <li class="active"><i class="fa fa-eye"></i> {{tr('view_languages')}}</li>
@endsection

@section('content')

    <div class="row">

        <div class="col-xs-12">
        
            @include('notification.notify')

            <div class="box box-primary">

                <div class="box-header label-primary">
                    <b>@yield('title')</b>
                    <a href="{{route('admin.languages.create')}}" style="float:right" class="btn btn-default"> <i class="fa fa-plus"></i> {{tr('create_language')}}</a>
                </div>

                <div class="box-body">
                    
                    @if(count($languages) != 0)

              	    <table id="example1" class="table table-bordered table-striped">

                        <thead>
                            <tr>
                                <th>{{tr('id')}}</th>
                                <th>{{tr('language') }}</th>
                                <th>{{tr('short_name')}}</th>
                                <th>{{tr('auth_file')}}</th>
                                <th>{{tr('messages_file')}}</th>
                                <th>{{tr('pagination_file')}}</th>
                                <th>{{tr('passwords_file')}}</th>
                                <th>{{tr('validation_file')}}</th>
                                <th>{{tr('status')}}</th>
                                <th>{{tr('action')}}</th>
                            </tr>                        
                        </thead>

                        <tbody>
                           
                            @foreach($languages as $i => $language_details)
                                <tr>

                                   <td>{{$i+$languages->firstItem()}}</td>

                                    <td>{{$language_details->language}}</td>
                                   
                                    <td>{{$language_details->folder_name}}</td>
                                    
                                    <td>
                                        <a href="{{route('admin.languages.download', ['folder_name' => $language_details->folder_name, 'file_name'=>'auth'] )}}" target="_blank">
                                            {{tr('download_here')}}
                                        </a>

                                    </td>
                                    
                                    <td>
                                        <a href="{{route('admin.languages.download', ['folder_name' => $language_details->folder_name, 'file_name'=>'messages'] )}}" target="_blank">
                                            {{tr('download_here')}}
                                        </a>

                                    </td>
                                    
                                    <td>
                                        <a href="{{route('admin.languages.download', ['folder_name'=>$language_details->folder_name, 'file_name'=>'pagination'] )}}" target="_blank">
                                            {{tr('download_here')}}
                                        </a>

                                    </td>
                                    
                                    <td>
                                        <a href="{{route('admin.languages.download', ['folder_name'=>$language_details->folder_name, 'file_name'=>'passwords'] )}}" target="_blank">
                                            {{tr('download_here')}}
                                        </a>

                                    </td>
                                    
                                    <td>
                                        <a href="{{route('admin.languages.download', ['folder_name'=>$language_details->folder_name, 'file_name'=>'validation'] )}}" target="_blank">
                                            {{tr('download_here')}}
                                        </a>

                                    </td>
                                    <td>
                                         @if($language_details->status == APPROVED)
                                            <span class="label label-success">{{tr('active')}}</span>
                                        @else
                                            <span class="label label-warning">{{tr('inactive')}}</span>
                                        @endif
                                    </td>

                                    <td>

                                       <div class="dropdown">
                                        
                                            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                {{tr('action')}}
                                                <span class="caret"></span>
                                            </button>

                                                 <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
                                                
                                                @if($i != 0)
                                                
                                                    @if(Setting::get('admin_delete_control') == YES)
                                                        <li>
                                                            <a href="javascript:;" class="btn disabled" style="text-align: left"><b><i class="fa fa-edit"></i>&nbsp;{{tr('edit')}}</b></a>
                                                        </li> 

                                                        <li>
                                                            <a href="javascript:;" class="btn disabled" style="text-align: left"><b><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</b></a>
                                                        </li>

                                                    @else

                                                        <li>
                                                            <a href="{{route('admin.languages.edit', ['language_id' => $language_details->id ] ) }}"><b><i class="fa fa-edit"></i>&nbsp;{{tr('edit')}}</b></a>
                                                        </li> 

                                                        <li>
                                                            <a onclick="return confirm(&quot;{{ tr('admin_language_delete_confirmation' , $language_details->language) }}&quot;);" href="{{route('admin.languages.delete', ['language_id' => $language_details->id ] ) }}"><b><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</b></a>
                                                        </li> 
                                                    @endif            

                                                @endif    

                                                <li>

                                                    @if(count($languages) > 1)

                                                    <a href="{{route('admin.languages.status', ['language_id' => $language_details->id ] )}}"><b>
                                                    @if($language_details->status == APPROVED )
                                                        <i class="fa fa-close"></i>&nbsp;{{tr('inactivate')}}
                                                    @else
                                                        <i class="fa fa-check"></i>&nbsp;{{tr('activate')}}
                                                    @endif
                                                    </b>
                                                    </a>

                                                    @else

                                                     <a href="javascript:void(0);" disabled style="color: red;cursor: no-drop" title="This option will enable when more than one languages !!!">{{$language_details->status == APPROVED ? tr('inactivate') : tr('activate')}}</a>

                                                    @endif
                                                </li>

                                                
                                                @if($language_details->folder_name != Setting::get('default_lang'))
                                                <li>
                                                    <a href="{{route('admin.languages.set_default', ['language_file' => $language_details->folder_name] )}}"><b>
                                                        <i class="fa fa-globe"></i>&nbsp;{{tr('set_default_language')}}
                                                        </b>
                                                    </a>
                                                </li>

                                                @endif

                                            </ul>

                                        </div>

                                    </td>
                                    
                                </tr>                            
                            @endforeach

    					</tbody>

					</table>
                
                    @else
                        <h3 class="no-result">{{ tr('no_language_found') }}</h3>
                    @endif
                </div>

            </div>
        
        </div>
    
    </div>

@endsection


@if(Session::has('flash_language'))

@section('scripts')

<script type="text/javascript" src="{{asset('common/js/bootbox.min.js')}}"></script>
<script type="text/javascript">

bootbox.confirm("Do you want to reload the page to view default language ?", function(result){ 
    if (result == true) {
        window.location.reload(true);
    }
    console.log('This was logged in the callback: ' + result); 
});

</script>
@endsection

@endif