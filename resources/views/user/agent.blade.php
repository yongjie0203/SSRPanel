@extends('user.layouts')
@section('css')
    <link href="/assets/pages/css/search.min.css" rel="stylesheet" type="text/css" />
    <style>
    		.coupons {margin:20px;}
		.coupons div{display:inline-grid;bolder:1px;}
		.notuse{}
		.used{}		
		.notuse div:active{background-color:red}
		.notuse div {background-color:#659be0;color:white;display:block;margin:2px;padding:2px;}
		.used div {background-color:#e7505a; color:white;display:block;margin:2px;padding:2px;}		
		.used div:active{background-color:red;}
	</style>
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top: 0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div class="row">
            <div class="col-md-12">
                <div class="search-page search-content-1">
		<div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption font-dark">
                            <span class="caption-subject bold uppercase"> 代金券列表（每种金额仅显示前5条数据） </span>
                        </div>                        
                    </div>
		    <div class="portlet-body">
			    <div class="row">
				<div class="coupons">
				    <div class="notuse" id="n2490"><div style="background-color:red" >24.9元</div></div>
				    <div class="notuse" id="n5490"><div style="background-color:red">54.9元</div></div>
				    <div class="notuse" id="n9980"><div style="background-color:red">99.8元</div></div>
				    <div class="notuse" id="n17980"><div style="background-color:red">179.8元</div></div>
				    <div class="used" id="used"><div style="background-color:red">已失效</div></div>                             
				</div>
			   </div>
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
                        
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-4 col-xs-12">
                                <input type="text" class="col-md-4 col-sm-4 col-xs-12 form-control" name="username" value="{{Request::get('username')}}" id="username" placeholder="用户名" onkeydown="if(event.keyCode==13){doSearch();}">
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
                                    <th> 端口 </th>
                                    <th> 用户名 </th>
                                    <th> 套餐购买 </th>                          
                                    <th> 已消耗 </th>
                                    <th> 最后使用 </th>
                                    <th> 有效期 </th>
                                    <th> 状态 </th>
                                    <th> 代理 </th>
                                   
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
                                                <td> {{$user->port}} </td>
                                                <td> {{$user->username}} </td>
                                                <td> 						  
								<select style="width:60px" >
								    <option value="" >请选择</option>								    
								    <option value="3" >30天</option>
								    <option value="10" >90天</option>
								    <option value="9" >180天</option>
								    <option value="8" >360天</option>
								</select>
								<input type="text"  placeholder="券码" style="width:80px" />
								<a class="buy" uname="{{$user->username}}" uid="{{$user->id}}"  >应用</a>							  
						</td>
                                               
                                                <td class="center"> {{$user->used_flow}} </td>
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
	
	$(".buy").on("click",function(){
            var uid = $(this).attr("uid");
	    var uname = $(this).attr("uname");
	    var gid = $(this).parent().find("select").val();
	    var gname = $(this).parent().find("select").find("option:selected").text();
	    var dcode = $(this).parent().find("input").val();
	    var msg = "您即将使用代金券:"+ dcode +"：为用户:"+ uname +"购买:" + gname +"，以上信息是否正确？";
	    layer.confirm(msg, {
		  btn: ['确定','信息有误'] //按钮
		}, function(){
		  	index = layer.load(1, {
				shade: [0.7,'#CCC']
	                });
		
			$.ajax({
			    type: "POST",
			    url: "{{url('agent/buy')}}" + "/" + gid,
			    data:{_token:'{{csrf_token()}}',uid:uid,coupon_sn:dcode},
			    async: false,                  
			    dataType: 'json',
			    beforeSend: function () {
				index = layer.load(1, {
				    shade: [0.7,'#CCC']
				});
			    },
			    success: function (ret) {
				layer.msg(ret.message, {time:1300}, function() {
					if (ret.status == 'success') {
					    window.location.reload();
					} else {
					    layer.close(index);
					}
				    });
				}
			    });
		}, function(){
		  
		});
	    
            
        });
	  // 搜索
        function doSearch() {
            var username = $("#username").val();
           
            window.location.href = '{{url('agent/userList')}}' + '?username=' + username ;
        }

        // 重置
        function doReset() {
            window.location.href = '{{url('agent/userList')}}';
        }
	

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
				    var head = getCouponHead(this.amount);
                                    var div = '<div class="mt-clipboard" data-clipboard-action="copy" data-clipboard-text="' + head+ this.sn + '">';
                                    div = div + head + this.sn;
                                    div = div + "</div>";
                                    $("#n"+amount).append(div);
                                });
                           }else{//不可用
                                $(ret.data).each(function(){
				    var head = getCouponHead(this.amount);
                                    var div = "<div>";
                                    div = div +  head + this.sn;
                                    div = div + "</div>";
                                    $("#used").append(div);
                                });
                           }   
                        }                       
                    }
                });
             }
         
	     function getCouponHead(amount){
	     	if(24.90 == amount){
		    return "1";
		} 
		if(54.90 == amount){
		    return "2";
		} 
		if(99.80 == amount){
		    return "3";
		} 
		if(179.80 == amount){
		     return "4";
		}
		return "";
	     }
         
         });
    </script>
    <script src="/assets/global/plugins/clipboardjs/clipboard.min.js" type="text/javascript"></script>
    <script src="/assets/pages/scripts/components-clipboard.min.js" type="text/javascript"></script>
@endsection
