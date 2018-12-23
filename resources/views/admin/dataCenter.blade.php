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
             nodeUsedMonthly();
             var option = {title:{text:'节点近30天内使用量'},color:['#3398DB'],tooltip:{trigger:'axis',axisPointer:{type:'shadow'}},grid:{left:'3%',right:'4%',bottom:'3%',containLabel:true},xAxis:[{type:'category',axisLabel:{interval:0,rotate:-40,},axisTick:{alignWithLabel:true}}],yAxis:[{type:'value'}],series:[{name:'用量',type:'bar',barWidth:'60%'}]};
             
             function nodeUsedMonthly(){
                 $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/nodeUsedMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            var option0 = option;
                            option0.title.text = "节点近30天内使用量";
                            option0.xAxis.data = ret.data.x;
                            option0.series.data = ret.data.y;
                            option0.series.type = "bar";
                            nodeUsedMonthlyEchart.setOption(option0);
                        }                       
                    }
                });
             }
             
             function userOnlinDataMonthly(){
                $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/userOnlineDataMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {  
                            var option1 = option;
                            option1.title.text = "近30天用户上网时间分布";
                            option1.xAxis.data = ret.data.hours;
                            option1.series.data = ret.data.users;
                            option1.series.type = "line";
                            nodeUsedMonthlyEchart.setOption(option1);
                        }                       
                    }
                });
             }
             
         });
    </script>
@endsection
