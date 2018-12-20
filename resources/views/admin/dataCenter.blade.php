@extends('admin.layouts')
@section('css')
@endsection
@section('content')
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content" style="padding-top:0;">
        <!-- BEGIN PAGE BASE CONTENT -->
        <div >
            ceshi
            <div id="node-used-monthly" ></div>
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
             nodeUsedMonthly();
             
             function nodeUsedMonthly(){
                 $.ajax({
                    type: "GET",
                    url: "{{url('dataCenter/nodeUsedMonthly')}}",
                    async: false,                  
                    success: function (ret) {                        
                        if (ret.status == 'success') {     
                         var option = {
                                title: {
                                    text: 'ECharts 入门示例'
                                },
                                tooltip: {},
                                legend: {
                                    data:['销量']
                                },
                                xAxis: {
                                    data: ["衬衫","羊毛衫","雪纺衫","裤子","高跟鞋","袜子"]
                                },
                                yAxis: {},
                                series: [{
                                    name: '销量',
                                    type: 'bar',
                                    data: [5, 20, 36, 10, 10, 20]
                                }]
                            };
                         /* var option = {
                                title: {
                                    text: '节点最近30天内使用量'
                                },
                                color: ['#3398DB'],
                                tooltip : {
                                    trigger: 'axis',
                                    axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                                        type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                                    }
                                },
                                grid: {
                                    left: '3%',
                                    right: '4%',
                                    bottom: '3%',
                                    containLabel: true
                                },
                                xAxis : [
                                    {
                                        type : 'category',
                                        data : ret.data.x,
                                        axisTick: {
                                            alignWithLabel: true
                                        }
                                    }
                                ],
                                yAxis : [
                                    {
                                        type : 'value'
                                    }
                                ],
                                series : [
                                    {
                                        name:'用量',
                                        type:'bar',
                                        barWidth: '60%',
                                        data:ret.data.y
                                    }
                                ]
                            };*/
                            nodeUsedMonthlyEchart.setOption(option);
                        }                       
                    }
                });
             
         });
    </script>
@endsection
