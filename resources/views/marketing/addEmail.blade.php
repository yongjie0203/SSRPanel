@extends('admin.layouts')
@section('css')
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top:0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div class="row">
            <div class="col-md-12">
                @if (Session::has('errorMsg'))
                    <div class="alert alert-danger">
                        <button class="close" data-close="alert"></button>
                        <strong>错误：</strong> {{Session::get('errorMsg')}}
                    </div>
                @endif
                <!-- BEGIN PORTLET-->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption">
                            <span class="caption-subject font-dark sbold uppercase">邮件群发</span>
                        </div>
                        <div class="actions"></div>
                    </div>
                    <div class="portlet-body form">
                        <!-- BEGIN FORM-->
                        <form action="{{url('admin/addEmail')}}" method="post" enctype="multipart/form-data" class="form-horizontal" onsubmit="return do_submit();">
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="control-label col-md-1">选择范围</label>
                                    <div class="col-md-11" >
                                        <div class="mt-checkbox-inline">
                                            <button type="button" id="selectUser" class="btn green">选择</button>
                                        </div>
                                         <label id="count_info" style="color:red;"></label>
                                    </div>

                               <div class="form-group">
                                    <label class="control-label col-md-1">使用模板</label>
                                    <div class="col-md-11">    
                                        <label class="mt-checkbox">
                                             <input type="checkbox" name="template" checked value="1" ></input>
                                             <span></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-1">邮件格式</label>
                                    <div class="col-md-11">
                                        <label class="mt-radio">
                                             <input type="radio" name="format" value="1" > html </input>
                                             <span></span>
                                        </label>
                                        <label class="mt-radio">
                                             <input type="radio" name="format" value="2" > markdown </input>
                                             <span></span>
                                         </label> 
                                    </div>
                                </div>

                                
                                <div class="form-group">
                                    <label class="control-label col-md-1">发送模式</label>
                                    <div class="col-md-11">
                                        <label class="mt-radio">
                                             <input type="radio" name="sendMode" value="1" > 单封单人 </input>
                                             <span></span>
                                        </label>
                                        <label class="mt-radio">
                                             <input type="radio" name="sendMode" value="2" > 单封多人 </input>
                                             <span></span>
                                         </label> 
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-1">收件人</label>
                                    <div class="col-md-10">
                                        <input type="text" class="form-control" name="summary" id="summary" placeholder="">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-1">主题</label>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="title" id="title" placeholder="" autofocus required>
                                        <input type="hidden" name="_token" value="{{csrf_token()}}">
                                    </div>
                                    <label class="control-label col-md-1">标题</label>
                                    <div class="col-md-4">
                                       <input type="text" class="form-control" name="title" id="title" placeholder=""/>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1">邮件内容</label>
                                    <div class="col-md-10">
                                        <script id="editor" type="text/plain" style="height:400px;"></script>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn green">保存发送任务</button>
                                        <button type="button" class="btn green">发送测试预览</button>
                                        <button type="button" class="btn green">启动群发任务</button>
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
    
   <div class="col-md-6" >
        <div id="userGroup"  style="display:none;">
            <div class="row">
                <div class="col-md-12">                
                    <div class="mt-checkbox-inline" >
                         @if(!$groupList->isEmpty())
                            @foreach($groupList as $group)
                                <label class="mt-checkbox">
                                <input type="checkbox" name="group" value="{{$group->id}}" > {{$group->name}}
                                <span></span>
                            </label>                                                   
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div id="selectedInfo" class="col-md-12" style="color:red;">
                </div>
            </div>


            <div class="row">
                <div class="col-md-12">                    
                    <button type="button" class="btn green">确定</button>
                </div>
            </div>


        </div>
     </div>
    <!-- END CONTENT BODY -->
@endsection
@section('script')
    <script src="/js/ueditor/ueditor.config.js" type="text/javascript" charset="utf-8"></script>
    <script src="/js/ueditor/ueditor.all.js" type="text/javascript" charset="utf-8"></script>

    <script type="text/javascript">
        // 百度富文本编辑器
        var ue = UE.getEditor('editor', {
            toolbars:[['source','undo','redo','bold','italic','underline','insertimage','insertvideo','lineheight','fontfamily','fontsize','justifyleft','justifycenter','justifyright','justifyjustify','forecolor','backcolor','link','unlink']],
            wordCount:true,                //关闭字数统计
            elementPathEnabled : false,    //是否启用元素路径
            maximumWords:300,              //允许的最大字符数
            initialContent:'',             //初始化编辑器的内容
            initialFrameWidth:null,        //初始化宽度
            autoClearinitialContent:false, //是否自动清除编辑器初始内容
        });

        // ajax同步提交
        function do_submit() {
            var _token = '{{csrf_token()}}';
            var title = $('#title').val();
            var type = $("input:radio[name='type']:checked").val();
            var author = $('#author').val();
            var summary = $('#summary').val();
            var content = UE.getEditor('editor').getContent();
            var sort = $('#sort').val();

            $.ajax({
                type: "POST",
                url: "{{url('admin/addEmail')}}",
                async: false,
                data: {_token:_token, title: title, type:type, author:author, summary:summary, content:content, sort:sort},
                dataType: 'json',
                success: function (ret) {
                    layer.msg(ret.message, {time:1000}, function() {
                        if (ret.status == 'success') {
                            window.location.href = '{{url('admin/emailList')}}';
                        }
                    });
                }
            });

            return false;
        }
        

        
        $(function() {
             $(document).on('click','#selectUser',function(){
                var width = $('#userGroup').outerWidth(true);
                var height = $('#userGroup').outerHeight(true);
                height = height + 100;
                height = height +'px';
                width = width + 100;
                width = width +'px'
                layer.open({
                      type: 1,     
                      title:'选择群发分组',
                      area: [width,height],
                      content: $("#userGroup")
                    });
             });
             
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
            
        });
        
        
    </script>
@endsection
