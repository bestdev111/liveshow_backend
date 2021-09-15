
<div class="row">

    <div class="col-md-10">

        <div class="box box-primary">

            <div class="box-header label-primary">
                <b style="font-size:18px;">@yield('title')</b>
                <a href="{{route('admin.admins.list')}}" class="btn btn-default pull-right">{{tr('view_admins')}}</a>
            </div>

            <form class="form-horizontal" action="{{route('admin.admins.save')}}" method="POST" enctype="multipart/form-data" role="form">

                <div class="box-body">


                    <input type="hidden" name="id" value="{{$model->id}}">

                    <div class="form-group">
                        <label for="username" class="col-sm-2 control-label">* {{tr('username')}}</label>

                        <div class="col-sm-10">
                            <input type="text" required name="name" 
                            pattern = "[a-zA-Z,0-9\s\-\.]{2,100}" title="{{tr('only_alphanumeric')}}" class="form-control" id="username" placeholder="{{tr('name')}}" value="{{$model->name ? $model->name : old('name')}}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="col-sm-2 control-label">* {{tr('email')}}</label>
                        <div class="col-sm-10">
                            <input type="email" maxlength="255" required class="form-control" id="email" name="email" pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,10}$" placeholder="{{tr('email')}}" value="{{$model->email ? $model->email : old('email')}}">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="mobile" class="col-sm-2 control-label">* {{tr('mobile')}}</label>

                        <div class="col-sm-10">
                            <input type="text" required name="mobile" class="form-control" id="mobile" placeholder="{{tr('mobile')}}" minlength="4" maxlength="16" pattern="[0-9]{4,16}" value="{{$model->mobile ? $model->mobile : old('mobile')}}">
                            <br>
                             <small style="color:brown">{{tr('mobile_note')}}</small>
                        </div>
                    </div>

                    @if(!$model->id)

                    <div class="form-group">
                        <label for="password" class="col-sm-2 control-label">* {{tr('password')}}</label>

                        <div class="col-sm-10">
                            <input type="password" required  name="password" pattern=".{6,}" title="{{tr('password_notes')}}" class="form-control" id="password" placeholder="{{tr('password')}}" value="{{old('password')}}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username" class="col-sm-2  control-label">* {{tr('password_confirmation')}}</label>

                        <div class="col-sm-10">
                            <input type="password" required pattern=".{6,}"  title="{{tr('password_notes')}}"  name="password_confirmation" class="form-control" id="username" placeholder="{{tr('password_confirmation')}}"  value="{{old('password_confirmation')}}">
                        </div>
                    </div>


                    @endif

                    <div class="form-group">
                        <label for="description" class="col-sm-2 control-label">* {{tr('description')}}</label>
                        <div class="col-sm-10">
                           <textarea class="form-control" name="description" placeholder="{{tr('description')}}">{{$model->description ? $model->description : old('description')}}</textarea>
                        </div>
                    </div>

                </div>

                <div class="box-footer">
                    <button type="reset" class="btn btn-danger">{{tr('cancel')}}</button>
                    @if(Setting::get('admin_delete_control'))
                        <a href="#" class="btn btn-success pull-right" disabled>{{tr('submit')}}</a>
                    @else
                        <button type="submit" class="btn btn-success pull-right">{{tr('submit')}}</button>
                    @endif
                </div>
                <input type="hidden" name="timezone" value="{{$model->timezone}}" id="userTimezone">
            </form>
        
        </div>

    </div>

</div>
