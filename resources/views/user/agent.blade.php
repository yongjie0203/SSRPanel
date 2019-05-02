@extends('user.layouts')
@section('css')
    <link href="/assets/pages/css/search.min.css" rel="stylesheet" type="text/css" />
    <style>
		.coupons div{display:inline-grid;bolder:1px;}
		.notuse{}
		.used{}
		.used div {background-color:red; color:white;display:block;margin:2px;padding:2px;}
		.notuse div {background-color:blue;color:white;display:block;margin:2px;padding:2px;}
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
