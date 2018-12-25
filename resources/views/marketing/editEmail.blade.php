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
                        <form action="{{url('marketing/editEmail')}}" method="post" enctype="multipart/form-data" class="form-horizontal" onsubmit="return do_submit();">
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="control-label col-md-1">选择范围</label>
                                    <div class="col-md-11" >
                                        <div class="mt-checkbox-inline">
                                            <button type="button" id="selectUser" class="btn green">选择</button>
                                        </div>
                                        <div id="displayGroups" style="display:inline;pading-left:10px;">
                                        </div>
                                         <label id="count_info" style="color:red;"></label>
                                    </div>

                               <div class="form-group">
                                    <label class="control-label col-md-1">使用模板</label>
                                    <div class="col-md-11">    
                                        <label class="mt-radio">                                            
                                             <input type="radio" name="template" {{$email->template == 0 ? 'checked' : ''}} value="0" >不使用系统模板</input>                                                                                         
                                             <span></span>
                                        </label>
                                        <label class="mt-radio">                                            
                                             <input type="radio" name="template" {{$email->template == 1 ? 'checked' : ''}} value="1" >使用系统模板</input>
                                             <span></span>
                                        </label>                                        
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-1">邮件格式</label>
                                    <div class="col-md-11">
                                        <label class="mt-radio">
                                             <input type="radio" name="format" value="1" {{$email->format == 1 ? 'checked' : ''}}  > html </input>
                                             <span></span>
                                        </label>
                                        <label class="mt-radio">
                                             <input type="radio" name="format" value="2" {{$email->format == 2 ? 'checked' : ''}}  > markdown </input>
                                             <span></span>
                                         </label> 
                                    </div>
                                </div>

                                
                                <div class="form-group">
                                    <label class="control-label col-md-1">发送模式</label>
                                    <div class="col-md-11">
                                        <label class="mt-radio">
                                             <input type="radio" name="mode" value="1" {{$email->mode == 1 ? 'checked' : ''}}  > 单封单人 </input>
                                             <span></span>
                                        </label>
                                        <label class="mt-radio">
                                             <input type="radio" name="mode" value="2" {{$email->mode == 2 ? 'checked' : ''}}  > 单封多人 </input>
                                             <span></span>
                                         </label> 
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-md-1">收件人</label>
                                    <div class="col-md-10">
                                        <input type="text" class="form-control" name="to" id="to" placeholder="" value="{{$email->to}}">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-1">主题</label>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="subject" id="subject" placeholder="" autofocus required value="{{$email->subject}}" >
                                        <input type="hidden" name="_token" value="{{csrf_token()}}">
                                    </div>
                                    <label class="control-label col-md-1">标题</label>
                                    <div class="col-md-4">
                                       <input type="text" class="form-control" name="title" id="title" placeholder="" value="{{$email->title }}" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1">邮件内容</label>
                                    <div class="col-md-10">
                                        <script id="editor" type="text/plain" style="height:400px;">{!! $email->content !!}</script>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="hidden" id="action" name="action"  value="save" ></input>                                        
                                        <button type="submit" onclick="$('#action').val($(this).attr('name'))" name="save" class="btn green">保存当前内容</button>
                                        <button type="submit" onclick="$('#action').val($(this).attr('name'))" name="test" class="btn green">在邮箱中预览</button>
                                        <button type="submit" onclick="$('#action').val($(this).attr('name'))" name="start" class="btn green">启动群发任务</button>
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
    
   <div class="col-md-6 col-xs-11" >
        <div id="userGroup"  style="display:none;">
            <div class="form-group">
                <div class="col-md-12">                
                    <div class="mt-checkbox-inline" >
                         @if(!$groupList->isEmpty())
                            @foreach($groupList as $group)
                                <label class="mt-checkbox">
                                <input type="checkbox" name="group" {{$group->checked}} value="{{$group->id}}" > {{$group->name}}
                                <span></span>
                            </label>                                                   
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div id="selectedInfo" class="col-md-12" style="color:red;line-height: 30px">
                
                </div>
            </div>


            <div class="form-group">
                <div class="col-md-12" style="text-align: right;padding-right: 30px;">                    
                    <button type="button" id="closeSelect" class="btn green">确定</button>
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
            maximumWords:1000,              //允许的最大字符数
            initialContent:'',             //初始化编辑器的内容
            initialFrameWidth:null,        //初始化宽度
            autoClearinitialContent:false, //是否自动清除编辑器初始内容
        });

        // ajax同步提交
        function do_submit() {
            var _token = '{{csrf_token()}}';
            var id = '{{$email->id}}';
            var title = $('#title').val();
            var template = $("input[name='template']:checked").val();
            var format = $("input[name='format']:checked").val();
            var mode = $("input[name='mode']:checked").val();
            var to = $('#to').val();
            var subject = $('#subject').val(); 
            var action = $('#action').val();
            var content = UE.getEditor('editor').getContent();
            if("2" == format){
                content = UE.getEditor('editor').getPlainTxt();
            }
            
            var groups = getSelectedGroup().join(",");

            $.ajax({
                type: "POST",
                url: "{{url('marketing/editEmail')}}",
                async: false,
                data: {_token:_token, title: title, groups:groups,template:template, format:format, mode:mode, content:content, to:to ,subject:subject,action:action,id:id},
                dataType: 'json',
                success: function (ret) {
                    layer.msg(ret.message, {time:1000}, function() {
                        if (ret.status == 'success') {
                            window.location.href = '{{url('marketing/emailList')}}';
                        }
                    });
                }
            });

            return false;
        }
        
        function getSelectedGroup(){
            var groups = new Array();
            $("input[name='group']:checked").each(function(){
                groups.push($(this).val())
            });
            return groups;
        }

        
        $(function() {
             var index;
             $(document).on('click','#selectUser',function(){
                var width = $('#userGroup').outerWidth(true);
                var height = $('#userGroup').outerHeight(true);
                height = height + 50;
                height = height +'px';
                width = width + 50;
                width = width +'px'
                index = layer.open({
                      type: 1,     
                      title:'选择群发分组',
                      area: [width,height],
                      content: $("#userGroup")
                    });
             });
             
             function displayGroupsInfo(){
                layer.close(index);
                var groupshtml = "";
                $("input[name='group']:checked").each(function(){
                   groupshtml = groupshtml + "<div style='display:inline;pading:5px ' >" + $(this).parent().text() + "</div>";
                });
                $('#displayGroups').html(groupshtml);
                $('#count_info').text($("#selectedInfo").text());
             }
             
             $(document).on('click','#closeSelect',function(){
                displayGroupsInfo();
             });
             
             function getCountInfo(){
                var groups = getSelectedGroup().join(",");               
                console.log(groups);             
                $.ajax({
                    type: "GET",
                    url: "{{url('marketing/getGroupCount')}}",
                    async: false,
                    data: {groups:groups},
                    dataType: 'json',
                    success: function (ret) {                        
                        if (ret.status == 'success') {
                            var count = "总用户数："+ret.data.total+"，已选择：" + ret.data.selected[0].selected+"，退订："+ ret.data.selected[0].blacked+"，转发：" +ret.data.selected[0].forward ;
                            $("#selectedInfo").text(count);
                        }                       
                    }
                });               
            }
             $(document).on('click', "input[name='group']", function(){
                getCountInfo();
             });
            
            //初始化分组数据
             getCountInfo();
             displayGroupsInfo();
                      
        });
        
        
    </script>
@endsection
