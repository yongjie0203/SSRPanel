@extends('admin.layouts')
@section('css')
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top:0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div class="row">
            <div class="col-md-12">
                <!-- BEGIN PORTLET-->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <span class="caption-subject font-dark sbold uppercase">添加群发分组</span>
                        </div>
                        <div class="actions"></div>
                    </div>
                    <div class="portlet-body form">
                        @if (Session::has('errorMsg'))
                            <div class="alert alert-danger">
                                <button class="close" data-close="alert"></button>
                                <strong>错误：</strong> {{Session::get('errorMsg')}}
                            </div>
                        @endif
                        <!-- BEGIN FORM-->
                        <form action="#" method="post" enctype="multipart/form-data" class="form-horizontal" role="form" onsubmit="return do_submit();">
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="col-md-1">分组名称</label>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="name" value="" id="name" placeholder="" required>
                                        <input type="hidden" name="_token" value="{{csrf_token()}}" />
                                    </div>
                                </div>
                                <div class="form-group" >
                                    <div class="col-md-10">
                                        <label for="type" class="col-md-1" style="padding:8px 0px 8px 0px;">用户状态</label>
                                        <div class="mt-radio-inline" style="padding-left:65px;">
                                            <label class="mt-checkbox">
                                                <input type="checkbox" class="setr" name="U" value="1" > 正常
                                                <span></span>
                                            </label>
                                            <label class="mt-checkbox">
                                                <input type="checkbox" class="setr" name="U" value="0"> 未激活
                                                <span></span>
                                            </label>
                                            <label class="mt-checkbox">
                                                <input type="checkbox" class="setr" name="U" value="-1"> 禁用
                                                <span></span>
                                            </label>
                                        </div>
                                    </div>
                                    </div>
                                    
                                    <div class="form-group" >
                                    <div class="col-md-10">
                                        <label for="label" class="col-md-1" style="padding:8px 0px 8px 0px;">用户标签</label>
                                        <div class="mt-checkbox-inline" style="padding-left:65px;">                                        
                                          @if(!$labelList->isEmpty())
                                                @foreach($labelList as $label)
                                                    <label class="mt-checkbox">
                                                    <input type="checkbox" class="setr" name="T" value="{{$label->id}}" > {{$label->name}}
                                                    <span></span>
                                                </label>                                                   
                                                @endforeach
                                            @endif                                       
                                        </div>
                                    </div>
                                    
                                    </div>
                                    
                                    <div class="form-group" >
                                    <div class="col-md-10">
                                        <label for="label" class="col-md-1" style="padding:8px 0px 8px 0px;" >用户等级</label>
                                        <div class="mt-checkbox-inline" style="padding-left:65px;">
                                             @if(!$levelList->isEmpty())
                                                @foreach($levelList as $level)
                                                    <label class="mt-checkbox">
                                                    <input type="checkbox" class="setr" name="L" value="{{$level->level}}" > {{$level->level_name}}
                                                    <span></span>
                                                </label>                                                   
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    </div>
                               
                                    </div>
                              </div>
                              <label id="count_info" style="color:red;"></label>
                            </div>
                            <div class="form-actions">
                                <div class="row">
                                    <div class="col-md-offset-3 col-md-4">
                                        <button type="submit" class="btn green">提交</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <!-- END FORM-->
                    </div>
                </div>
                <!-- END PORTLET-->
            </div>
        </div>
        <!-- END PAGE BASE CONTENT -->
    </div>
    <!-- END CONTENT BODY -->
@endsection
@section('script')
    <script type="text/javascript">
        // ajax同步提交
        function do_submit() {
            var _token = '{{csrf_token()}}';
            var name = $('#name').val();
            var u = getStatusRange().join(",");
            var t = getTagRange().join(",");
            var l = getLevelRange().join(",");
            $.ajax({
                type: "POST",
                url: "{{url('marketing/addGroup')}}",
                async: false,
                data: {_token:_token, name:name, u:u, t:t, l:l},
                dataType: 'json',
                success: function (ret) {
                    layer.msg(ret.message, {time:1000}, function() {
                        if (ret.status == 'success') {
                            window.location.href = '{{url('marketing/groupList')}}';
                        }
                    });
                }
            });

            return false;
        }
        
        function getStatusRange(){
            var statusRange = new Array();
            $("input[name='U']:checked").each(function(){
                statusRange.push($(this).val())
            });
            return statusRange;
        }

        function getTagRange(){
            var tagRange = new Array();
            $("input[name='T']:checked").each(function(){
                tagRange.push($(this).val())
            });
            return tagRange;
        }

        function getLevelRange(){
            var levelRange = new Array();
            $("input[name='L']:checked").each(function(){
                levelRange.push($(this).val())
            });
            return levelRange;
        }
        
         $(function() {
             $(document).on('click', 'input.setr', function(){
                var u = getStatusRange().join(",");
                var t = getTagRange().join(",");
                var l = getLevelRange().join(",");
                console.log(u);
                console.log(t);
                console.log(l);
                $.ajax({
                    type: "GET",
                    url: "{{url('marketing/getCount')}}",
                    async: false,
                    data: {u:u, t:t, l:l},
                    dataType: 'json',
                    success: function (ret) {                        
                        if (ret.status == 'success') {
                            var count = "总用户数："+ret.data.total+"，已选择：" + ret.data.selected[0].selected+"，退订："+ ret.data.selected[0].blacked+"，转发：" +ret.data.selected[0].forward ;
                            $("#count_info").text(count);
                        }                       
                    }
                });
               
            });
            
            
        });
    </script>
@endsection
