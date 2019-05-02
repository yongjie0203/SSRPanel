@extends('user.layouts')
@section('css')
    <link href="/assets/pages/css/search.min.css" rel="stylesheet" type="text/css" />
    <style>
		.coupons div{display:inline-grid;bolder:1px;}
		.notuse{}
		.used{}		
		.notuse div:active{background-color:green}
		.notuse div {background-color:blue;color:white;display:block;margin:2px;padding:2px;}
		.used div {background-color:red; color:white;display:block;margin:2px;padding:2px;}		
		.used div:active{background-color:green}
	</style>
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top: 0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div class="row">
            <div class="col-md-12">
                <div class="search-page search-content-1">
                    <div class="row">
                        <div class="coupons">
                            <div class="notuse" id="n2490"><div style="background-color:red" >24.9元</div></div>
                            <div class="notuse" id="n5490"><div style="background-color:red">54.9元</div></div>
                            <div class="notuse" id="n9980"><div style="background-color:red">99.8元</div></div>
                            <div class="notuse" id="n17980"><div style="background-color:red">179.8元</div></div>
                            <div class="used" id="used"><div style="background-color:red">已失效</div></div>                             
                        </div>
                   </div>
                   
                   <div class="row">
            <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption font-dark">
                            <span class="caption-subject bold uppercase"> 用户列表 </span>
                        </div>
                        <div class="actions">
                            <div class="btn-group btn-group-devided">
                                <button class="btn sbold red" onclick="exportSSJson()"> 导出JSON </button>
                                <button class="btn sbold blue" onclick="batchAddUsers()"> 批量生成 </button>
                                <button class="btn sbold blue" onclick="addUser()"> 添加用户 </button>
                            </div>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <input type="text" class="col-md-4 col-sm-4 col-xs-12 form-control" name="username" value="{{Request::get('username')}}" id="username" placeholder="用户名" onkeydown="if(event.keyCode==13){doSearch();}">
                            </div>
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <input type="text" class="col-md-4 col-sm-4 col-xs-12 form-control" name="wechat" value="{{Request::get('wechat')}}" id="wechat" placeholder="微信" onkeydown="if(event.keyCode==13){doSearch();}">
                            </div>
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <input type="text" class="col-md-4 col-sm-4 col-xs-12 form-control" name="qq" value="{{Request::get('qq')}}" id="qq" placeholder="QQ" onkeydown="if(event.keyCode==13){doSearch();}">
                            </div>
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <input type="text" class="col-md-4 form-control" name="port" value="{{Request::get('port')}}" id="port" placeholder="端口" onkeydown="if(event.keyCode==13){doSearch();}">
                            </div>
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <select class="form-control" name="pay_way" id="pay_way" onChange="doSearch()">
                                    <option value="" @if(Request::get('pay_way') == '') selected @endif>付费方式</option>
                                    <option value="0" @if(Request::get('pay_way') == '0') selected @endif>免费</option>
                                    <option value="1" @if(Request::get('pay_way') == '1') selected @endif>月付</option>
                                    <option value="2" @if(Request::get('pay_way') == '2') selected @endif>季付</option>
                                    <option value="3" @if(Request::get('pay_way') == '3') selected @endif>半年付</option>
                                    <option value="4" @if(Request::get('pay_way') == '4') selected @endif>年付</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <select class="form-control" name="status" id="status" onChange="doSearch()">
                                    <option value="" @if(Request::get('status') == '') selected @endif>账号状态</option>
                                    <option value="-1" @if(Request::get('status') == '-1') selected @endif>禁用</option>
                                    <option value="0" @if(Request::get('status') == '0') selected @endif>未激活</option>
                                    <option value="1" @if(Request::get('status') == '1') selected @endif>正常</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <select class="form-control" name="enable" id="enable" onChange="doSearch()">
                                    <option value="" @if(Request::get('enable') == '') selected @endif>代理状态</option>
                                    <option value="1" @if(Request::get('enable') == '1') selected @endif>启用</option>
                                    <option value="0" @if(Request::get('enable') == '0') selected @endif>禁用</option>
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
                                    <th> 用户名 </th>
                                    <th> 端口 </th>
                                    <th> 加密方式 </th>
                                    <!--<th> 协议 </th>
                                    <th> 混淆 </th>-->
                                    <th> 已消耗 </th>
                                    <th> 最后使用 </th>
                                    <th> 有效期 </th>
                                    <th> 状态 </th>
                                    <th> 代理 </th>
                                    <th> 操作 </th>
                                </tr>
                                </thead>
                                <tbody>
                                    @if ($userList->isEmpty())
                                        <tr>
                                            <td colspan="12" style="text-align: center;">暂无数据</td>
                                        </tr>
                                    @else
                                        @foreach ($userList as $user)
                                            <tr class="odd gradeX {{$user->trafficWarning ? 'danger' : ''}}">
                                                <td> <a href="javascript:switchToUser('{{$user->id}}');">{{$user->id}} </a> </td>
                                                <td> {{$user->username}} </td>
                                                <td> <span class="label label-danger"> {{$user->port ? $user->port : '未分配'}} </span> </td>
                                                <td> <span class="label label-default"> {{$user->method}} </span> </td>
                                                <!--<td> <span class="label label-default"> {{$user->protocol}} </span> </td>
                                                <td> <span class="label label-default"> {{$user->obfs}} </span> </td>-->
                                                <td class="center"> {{$user->used_flow}} / {{$user->transfer_enable}} </td>
                                                <td class="center"> {{empty($user->t) ? '未使用' : date('Y-m-d H:i:s', $user->t)}} </td>
                                                <td class="center">
                                                    @if ($user->expireWarning == '-1')
                                                        <span class="label label-danger"> {{$user->expire_time}} </span>
                                                    @elseif ($user->expireWarning == '0')
                                                        <span class="label label-warning"> {{$user->expire_time}} </span>
                                                    @elseif ($user->expireWarning == '1')
                                                        <span class="label label-default"> {{$user->expire_time}} </span>
                                                    @else
                                                        {{$user->expire_time}}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($user->status > 0)
                                                        <span class="label label-info">正常</span>
                                                    @elseif ($user->status < 0)
                                                        <span class="label label-danger">禁用</span>
                                                    @else
                                                        <span class="label label-default">未激活</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($user->enable)
                                                        <span class="label label-info">启用</span>
                                                    @else
                                                        <span class="label label-danger">禁用</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="javascript:;" aria-expanded="false"> 操作
                                                            <i class="fa fa-angle-down"></i>
                                                        </a>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a href="javascript:editUser('{{$user->id}}');"> 编辑 </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:delUser('{{$user->id}}');"> 删除 </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:doExport('{{$user->id}}');"> 配置信息 </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:doMonitor('{{$user->id}}');"> 流量概况 </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:resetTraffic('{{$user->id}}');"> 流量清零 </a>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:switchToUser('{{$user->id}}');"> 切换身份 </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-sm-4">
                                <div class="dataTables_info" role="status" aria-live="polite">共 {{$userList->total()}} 个账号</div>
                            </div>
                            <div class="col-md-8 col-sm-8">
                                <div class="dataTables_paginate paging_bootstrap_full_number pull-right">
                                    {{ $userList->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END EXAMPLE TABLE PORTLET-->
            </div>
        </div>
                   
                </div>
            </div>
        </div>
        <!-- END PAGE BASE CONTENT -->
    </div>
    <!-- END CONTENT BODY -->
@endsection
@section('script')

<script type="text/javascript">
         $(function() { 
         var loadlimit = 5;
         loadCoupons(2490,0);
         loadCoupons(5490,0);
         loadCoupons(9980,0);
         loadCoupons(17980,0);
	 loadCoupons('',1);
         
          function loadCoupons(amount,status){
                 $.ajax({
                    type: "GET",
                    url: "{{url('agent/coupons')}}",
                    data:{amount:amount,status:status,limit:loadlimit},
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                           if(status==0){//可用
                                $(ret.data).each(function(){
                                    var div = '<div class="mt-clipboard" data-clipboard-action="copy" data-clipboard-text="' + this.sn + '">';
                                    div = div + this.sn;
                                    div = div + "</div>";
                                    $("#n"+amount).append(div);
                                });
                           }else{//不可用
                                $(ret.data).each(function(){
                                    var div = "<div>";
                                    div = div + this.sn;
                                    div = div + "</div>";
                                    $("#used").append(div);
                                });
                           }   
                        }                       
                    }
                });
             }
         
         
         });
    </script>
    <script src="/assets/global/plugins/clipboardjs/clipboard.min.js" type="text/javascript"></script>
    <script src="/assets/pages/scripts/components-clipboard.min.js" type="text/javascript"></script>
@endsection
