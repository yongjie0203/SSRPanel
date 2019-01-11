@extends('admin.layouts')
@section('css')
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top:0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div >          
            <div id="node-used-cyclicity" style="width:95%;height:300px" ></div>
            <div id="node-used-monthly" style="width:95%;height:300px" ></div>
            <div id="user-online-data-monthly" style="width:95%;height:300px" ></div>
            <div id="order-data-monthly" style="width:95%;height:300px" ></div>
            <div id="online-users-monthly" style="width:95%;height:300px" ></div>
        </div> 
        
        
     
        <!-- END PAGE BASE CONTENT -->
    </div>
    <!-- END CONTENT BODY -->
@endsection
@section('script')
    <script src="/assets/global/plugins/echarts/echarts.min.js" type="text/javascript"></script>
    
    <script type="text/javascript">
         $(function() {
         
             var nodeUsedCyclicityEchart = echarts.init(document.getElementById('node-used-cyclicity'));
             var nodeUsedMonthlyEchart = echarts.init(document.getElementById('node-used-monthly'));
             var userOnlineDataMonthlyEchart = echarts.init(document.getElementById('user-online-data-monthly'));
             var orderDataMonthlyEchart = echarts.init(document.getElementById('order-data-monthly'));
             var onlineUsersMonthlyEchart = echarts.init(document.getElementById('online-users-monthly'));
             
             nodeUsedCyclicity();
             nodeUsedMonthly();
             userOnlinDataMonthly();
             orderDataMonthly();
             onlineUsersMonthly();
             
             //流量重置日至今使用量百分百
             function nodeUsedCyclicity(){
                 $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/nodeUsedCyclicity')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            //var option = {title:{text:'重置日至今使用总量占比'},color:['#3398DB'],tooltip:{trigger: 'item',formatter: '{b}:\n{c}%',axisPointer:{type:'shadow'}},grid:{left:'3%',right:'4%',bottom:'3%',containLabel:true},xAxis:[{type:'category',data:ret.data.name,axisTick:{alignWithLabel:true},axisLabel:{interval:0,rotate:-40},axisTick:{alignWithLabel:true}}],yAxis:[{type:'value',axisLabel: {show: true,interval: 'auto',formatter: '{value} %' }}],series:[{name:'用量',type:'bar',barWidth:'60%',data:ret.data.percent}]};
                            var option = {title:{text:'节点流量占总可用流量比例',subtext:'最近30天'},tooltip:{trigger:'axis'},legend:{data:['最近30天']},toolbox:{show:true,feature:{mark:{show:true},dataView:{show:true,readOnly:false},magicType:{show:true,type:['bar']},restore:{show:true},saveAsImage:{show:true}}},calculable:true,xAxis:[{type:'value',boundaryGap:[0,0.01]}],yAxis:[{type:'category',data:ret.data.name}],series:[{name:'占比',type:'bar',data:ret.data.percent}]};
                            nodeUsedCyclicityEchart.setOption(option);                           
                        }                       
                    }
                });
             }
             
             //节点30天用量
             function nodeUsedMonthly(){
                 $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/nodeUsedMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            //var option = {title:{text:'节点近30天内使用量'},color:['#3398DB'],tooltip:{trigger:'axis',axisPointer:{type:'shadow'}},grid:{left:'3%',right:'4%',bottom:'3%',containLabel:true},xAxis:[{type:'category',data:ret.data.x,axisTick:{alignWithLabel:true},axisLabel:{interval:0,rotate:-40},axisTick:{alignWithLabel:true}}],yAxis:[{type:'value'}],series:[{name:'用量',type:'bar',barWidth:'60%',data:ret.data.y}]};
                            var option = {title:{text:'节点近30天内使用量',subtext:'单位:GB'},tooltip:{trigger:'axis'},legend:{data:['最近30天']},toolbox:{show:true,feature:{mark:{show:true},dataView:{show:true,readOnly:false},magicType:{show:true,type:['bar']},restore:{show:true},saveAsImage:{show:true}}},calculable:true,xAxis:[{type:'value',boundaryGap:[0,0.01]}],yAxis:[{type:'category',data:ret.data.x}],series:[{name:'用量',type:'bar',data:ret.data.y}]};
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
                            var option = {title:{text:'近30天用户在线时间分布'},xAxis:{type:'category',data:ret.data.hours},yAxis:{type:'value'},series:[{data:ret.data.users,type:'line',itemStyle : { normal: {label : {show: true}}} }]};;
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
                            var option = {title:{text:'近30天订单额'},xAxis:{type:'category',data:ret.data.date,axisLabel:{interval:0,rotate:-40}},yAxis:{type:'value'},series:[{data:ret.data.amount,type:'line',itemStyle : { normal: {label : {show: true}}} }]};;
                            orderDataMonthlyEchart.setOption(option);                            
                        }                       
                    }
                });
             }
             
             //30天内每天在线用户数
             function onlineUsersMonthly(){
                $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/onlineUsersMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            var option = {title:{text:'近30天用户在线数量'},xAxis:{type:'category',data:ret.data.date,axisLabel:{interval:0,rotate:-40}},yAxis:{type:'value'},series:[{data:ret.data.users,type:'line',itemStyle : { normal: {label : {show: true}}} }]};;
                            onlineUsersMonthlyEchart.setOption(option);                            
                        }                       
                    }
                });
             }
             
         });
    </script>
@endsection
