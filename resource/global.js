$(document).ready(function(){
	$("#funcSave").click(function(){
		var post = {
			'function' : editor.getValue(),
			'lang' : $("#langSelect").val(),
			'func_id' : $("#func_id").val()
		};
		$.post('./func.save', post, function(result){
			data = $.parseJSON(result);
			if(data.result) alert(data.data);
			else alert(data.msg);
		});
	});

	$("#funcAdd").click(function(){
		var post = {
			'name' : $("#func").val(),
			'obj_id' : $("#obj_id").val()
		};
		$.post('./func.add', post, function(result){
			data = $.parseJSON(result);
			if(data.result){
				alert(data.data);
				window.location.reload();
			}else alert(data.msg);
		});
	});

	$("#funcRemove").click(function(){
		if(!confirm("确定要删除方法吗？")) return;
		var post = {
			'func_id' : $("#func_id").val()
		};
		$.post('./func.remove', post, function(result){
			data = $.parseJSON(result);
			if(data.result){
				alert(data.data);
				window.location.href = "object=" + $("#obj_id").val();
			}else alert(data.msg);
		});
	});

	$("#objAdd").click(function(){
		var post = {
			'name' : $("#obj_name").val()
		};
		$.post('./obj.add', post, function(result){
			data = $.parseJSON(result);
			if(data.result){
				alert('添加成功');
				window.location.href = "object=" + data.data;
			}else alert(data.msg);
		});
	});

	$("#objRemove").click(function(){
		if(!confirm("确定要删除对象吗？")) return;
		var post = {
			'obj_id' : $("#obj_id").val()
		};
		$.post('./obj.remove', post, function(result){
			data = $.parseJSON(result);
			if(data.result){
				alert(data.data);
				window.location.href = "./";
			}else alert(data.msg);
		});
	});

	$("#engineSwitch").change(function(){
		var post = {
			'status' : $(this).val()
		};
		$.post('./engine.switch', post, function(result){
			data = $.parseJSON(result);
			if(data.result){
				alert(data.data);
				window.location.reload();
			}else alert(data.msg);
		});		
	})
});