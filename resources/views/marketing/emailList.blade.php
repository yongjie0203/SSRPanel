@extends('admin.layouts')
@section('css')
    <link href="/assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top:0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div class="row">
            <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption font-dark">
                            <span class="caption-subject bold uppercase"> 邮件群发列表 </span>
                        </div>
                        <div class="actions">
                            <div class="btn-group btn-group-devided">
                                <button class="btn sbold blue" onclick="send()"> 群发邮件 </button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <select class="form-control" id="sel-status" onChange="doSearch()">
                                    <option value="" @if(Request::get('status') == '') selected @endif>状态</option>
                                    <option value="0" @if(Request::get('status') == '0') selected @endif>未发送</option>
                                    <option value="2,3" @if(Request::get('status') == '2,3') selected @endif>发送中</option>
                                    <option value="4" @if(Request::get('status') == '4') selected @endif>暂停</option>
                                    <option value="1" @if(Request::get('status') == '1') selected @endif>已发送</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <button type="button" class="btn blue" onclick="doSearch();">查询</button>
                                <button type="button" class="btn grey" onclick="doReset();">重置</button>
                            </div>
                        </div>
                        <div class="table-scrollable table-scrollable-borderless">
                            <table class="table table-hover table-light">
                                <thead>
                                <tr>
                                    <th> # </th>
                                    <th> 邮件主题 </th>
                                    <th> 发送分组 </th>
                                    <th> 邮件状态</th>
                                    <th> 创建时间 </th>
                                    <th> 阅读人数/发送人数/预期发送 </th>
                                    <th> 操作 </th>
                                </tr>
                                </thead>
                                <tbody>
                                @if ($list->isEmpty())
                                    <tr>
                                        <td colspan="6" style="text-align: center;">暂无数据</td>
                                    </tr>
                                @else
                                    @foreach($list as $vo)
                                        <tr class="odd gradeX">
                                            <td> {{$vo->id}} </td>
                                            <td> <a href="/marketing/email?id={{$vo->id}}" >{{$vo->subject}}</a> </td>
                                            <td> {{$vo->groups}} </td>
                                            <td> {{$vo->statusLabel}} </td>
                                            <td> {{$vo->created_at}} </td>
                                            <td> {{$vo->read}}/{{$vo->send}}/{{$vo->total}} </td>
                                            <td>
                                                <button type="button" class="btn btn-sm blue btn-outline" onclick="editEmail('{{$vo->id}}')">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-sm-4">
                                <div class="dataTables_info" role="status" aria-live="polite">共 {{$list->total()}} 条邮件</div>
                            </div>
                            <div class="col-md-8 col-sm-8">
                                <div class="dataTables_paginate paging_bootstrap_full_number pull-right">
                                    {{ $list->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END EXAMPLE TABLE PORTLET-->
            </div>
        </div>
        <!-- END PAGE BASE CONTENT -->
    </div>
    <form action="{{url('marketing/emailList')}}" method="post" id="searchForm" class="form-horizontal">
        <input type="hidden" id="status" name="status" value="" />
        <input type="hidden" id="_token" name="_token" value="{{csrf_token()}}" />
    </form>
    <!-- END CONTENT BODY -->
@endsection
@section('script')
    <script type="text/javascript">
        // 发送邮件
        function send() {
            //layer.msg("开发中，敬请期待");
            window.location.href ="/marketing/addEmail";
        }
        
        function editEmail(id){
            window.location.href ="/marketing/editEmail?id="+id;
        }
        
        function doSearch(){
            $("#status").val($("#sel-status").val());
            $("#searchForm").submit();
        }
    </script>
@endsection
