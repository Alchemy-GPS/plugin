var ws=null;
// var ws_coinNetWorkType_1=null;

function socket_connection(connection_url) {
	// ws_coinNetWorkType_1 = ws_coinNetWorkType_0;
    ws = new WebSocket(connection_url);

    ws.onopen = function(){
        console.log("websocket 连接状态是：" +ws.readyState);
        ws.send("连接成功了啊！！！！")
    };
    //服务器消息
    ws.onmessage = function(evt){
        console.log(evt.data);
        var model=evt.data;
        if(model!="achpay pong"){
            orderStatus(model);
        }

    };
    //连接关闭
    ws.onclose = function(evt){
        console.log('WebSocketClosed!');
    };
    //连接错误
    ws.onerror = function(evt){
        console.log('WebSocketError!');
    };
    function orderStatus(data){
        var models=JSON.parse(data);
        console.log("--------------------");

        console.log(models);
        console.log("--------------------");
        console.log(models.data.confirmBlockNums);
        console.log(models.data.result);
        console.log(models.data.resultMsg);
       
        var blockNumes=models.data.confirmBlockNums;
        var status=models.data.result;
        var statusDesc=models.data.resultMsg;
        var cryptocurrencyId=models.data.cryptocurrencyId;
        var orderId = models.data.orderId;
        var merchantId = models.data.merchantId;

     

        if(status=="DEALING"){
            // swal("订单状态：未支付");
            console.log('订单状态：未支付')
        }
        
        if(status=="CONFIRMING"){
            // console.log('订单状态：支付确认中')
            swal("订单状态：支付确认中");
        }

        var y=0;
        if((status=="MSUCCESS" || status=="LSUCCESS")&& y==0){
            y=1;
            $("#orderStatus").css({color:"#ff0000"});
            $("#orderStatus").html("订单状态：支付"+statusDesc);
            orderStatusNotify(merchantId,orderId);//进行下后台通知
            if(status=="MSUCCESS") {
                getCallBackPageAddress(merchantId, orderId, 1);//页面回调
            }else{
                getCallBackPageAddress(merchantId, orderId, 0);//页面回调
            }

        }

        var i=0;
        if(status=="SUCCESS" && i==0) {
            i=1;
            // swal("支付成功！", '订单支付成功',"success");
            orderStatusNotify(merchantId,order_id);//进行下后台通知
            // getCallBackPageAddress(merchantId,orderId,1);//页面回调
        }
    }

    function orderStatusNotify(merchantId,order_id){

        $.ajax({
            url:'/',
            type:'post',
            dataType:'json',
            data:{is_check:'true',orderId:order_id,merchantId:merchantId},
            success:function(res){
                if(res.code==1){
                    swal("支付成功！", res.msg,"success");
                    window.location.href=res.url
                }else{
                    swal("支付失败！", res.msg,"error");
                }
            },
            error:function(error){
                alert(error)
            }
        })
    }

    function getCallBackPageAddress(merchantId,orderId,result) {

        $.ajax({
            type: "get",
            url: '/foundation-gateway/callback/page/address',
            data: {"merchantId":merchantId,"orderId":orderId,"result":result},
            success: function (data) {
                if(data !=null && data!=""){
                    window.location.href=data;
                }
            },
            error:function (data) {
                console.log("没有查到订单相关回调页面地址.........")
            }
        });
    }
}