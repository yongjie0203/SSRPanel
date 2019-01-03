@extends('admin.layouts')
@section('css')
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top:0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div >          
            <div id="node-used-monthly" style="width:95%;height:300px" ></div>
            <div id="user-online-data-monthly" style="width:95%;height:300px" ></div>
            <div id="order-data-monthly" style="width:95%;height:300px" ></div>
        </div> 
        
        
     
        <!-- END PAGE BASE CONTENT -->
    </div>
    <!-- END CONTENT BODY -->
@endsection
@section('script')
    <script src="/assets/global/plugins/echarts/echarts.min.js" type="text/javascript"></script>
    
    <script type="text/javascript">
         $(function() {
             var nodeUsedMonthlyEchart = echarts.init(document.getElementById('node-used-monthly'));
             var userOnlineDataMonthlyEchart = echarts.init(document.getElementById('user-online-data-monthly'));
             var orderDataMonthlyEchart = echarts.init(document.getElementById('order-data-monthly'));
             nodeUsedMonthly();
             userOnlinDataMonthly();
             orderDataMonthly();
             
             //节点30天用量
             function nodeUsedMonthly(){
                 $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/nodeUsedMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            var option = {title:{text:'节点近30天内使用量'},color:['#3398DB'],tooltip:{trigger:'axis',axisPointer:{type:'shadow'}},grid:{left:'3%',right:'4%',bottom:'3%',containLabel:true},xAxis:[{type:'category',data:ret.data.x,axisTick:{alignWithLabel:true},axisLabel:{interval:0,rotate:-40},axisTick:{alignWithLabel:true}}],yAxis:[{type:'value'}],series:[{name:'用量',type:'bar',barWidth:'60%',data:ret.data.y}]};
                            nodeUsedMonthlyEchart.setOption(option);                           
                        }                       
                    }
                });
             }
             
             //用户30天在线分布情况
             function userOnlinDataMonthly(){
                $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/userOnlineDataMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            var option = {title:{text:'近30天用户在线时间分布'},xAxis:{type:'category',data:ret.data.hours},yAxis:{type:'value'},series:[{data:ret.data.users,type:'line'}]};;
                            userOnlineDataMonthlyEchart.setOption(option);                            
                        }                       
                    }
                });
             }
             
             //30天内订单数据
             function orderDataMonthly(){
                $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/orderDataMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            var option = {title:{text:'近30天订单额'},xAxis:{type:'category',data:ret.data.date},yAxis:{type:'value'},series:[{data:ret.data.amount,type:'line'}]};;
                            orderDataMonthlyEchart.setOption(option);                            
                        }                       
                    }
                });
             }
             
         });
    </script>
@endsection
