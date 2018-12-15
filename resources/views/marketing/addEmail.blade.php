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
                            <span class="caption-subject font-dark sbold uppercase">新建邮件</span>
                        </div>
                        <div class="actions"></div>
                    </div>
                    <div class="portlet-body form">
                        <!-- BEGIN FORM-->
                        <form action="{{url('admin/addEmail')}}" method="post" enctype="multipart/form-data" class="form-horizontal" onsubmit="return do_submit();">
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="control-label col-md-1">发送范围</label>
                                    <div class="col-md-6">
                                        <label for="type" class="control-label col-md-1">用户范围</label>
                                        <div class="mt-radio-inline">
                                            <label class="mt-radio">
                                                <input type="radio" name="type" value="1" checked> 全体用户
                                                <span></span>
                                            </label>
                                            <label class="mt-radio">
                                                <input type="radio" name="type" value="2"> 仅有效用户
                                                <span></span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="label" class="control-label col-md-1">用户标签</label>
                                        <div class="mt-checkbox-inline">
                                            @if($labelList->isEmpty()) 
                                                <span>暂无</span>
                                            @else
                                                @foreach($labelList as $label)
                                                    <label class="mt-checkbox">
                                                        <input type="checkbox" name="label" value="{{$label->id}}" > {{$label->name}}
                                                        <span></span>
                                                    </label>
                                                    
                                                @endforeach
                                            @endif
                                         
                                       
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="label" class="control-label col-md-1">用户等级</label>
                                        <div class="mt-checkbox-inline">
                                            <label class="mt-checkbox">
                                                <input type="checkbox" name="label" value="1" checked> 全体用户
                                                <span></span>
                                            </label>
                                            <label class="mt-checkbox">
                                                <input type="checkbox" name="label" value="2"> 仅有效用户
                                                <span></span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1">主题</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="title" id="title" placeholder="" autofocus required>
                                        <input type="hidden" name="_token" value="{{csrf_token()}}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1">收件人</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="summary" id="summary" placeholder="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-md-1">抄送</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="summary" id="summary" placeholder="">
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
                                        <button type="submit" class="btn green">提 交</button>
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
    </script>
@endsection
