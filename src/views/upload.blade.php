<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">

	<title>Upload file - webshark ${WEBSHARK_VER}</title>

	<link rel="stylesheet" href="{{ asset('vendor/webshark/static/css/webshark.css') }}" type="text/css">
	<meta name='csrf-token' content="{{ csrf_token() }}"/>
	<script src="{{ asset('vendor/webshark/static/js/d3.v4.min.js') }}" type="text/javascript"></script>
	<script src="{{ asset('vendor/webshark/static/js/webshark-app.js') }}" type="text/javascript"></script>

	<script type="text/javascript">
		var g_webshark_url = "{{ route('webshark.json') }}";

		var g_SIZE_LIMIT = 10 * 1024 * 1024; /* 10 MB */

		function form_upload(ev)
		{
			var stat  = document.getElementById('status');
			var files = document.getElementById('f').files;

			if (files.length != 1)
			{
				ev.preventDefault();
				return;
			}

			var f = files[0];

			if (f.size >= g_SIZE_LIMIT)
			{
				stat.innerHTML = 'Limit is ' + g_SIZE_LIMIT + '. Select other file';
				ev.preventDefault();
				return;
			}

			var xhr = new XMLHttpRequest();

			xhr.open('POST', "{{ route('webshark.upload') }}", true);

			xhr.upload.addEventListener("progress", function(e) {
				if (e.lengthComputable) {
					var percentage = Math.round((e.loaded * 100) / e.total);
					stat.innerHTML = 'Uploading: ' + f.name + ' ' + percentage + '%';
				}
			}, false);

			xhr.onload = function () {
				if (xhr.readyState == 4 && xhr.status === 200) {
					var file = JSON.parse(xhr.responseText);

					if (file['err'] != undefined)
					{
						stat.innerHTML = 'Uploaded, but not capture file.';
						return;
					}

					// file['_path'] = file['name'];
					// file['url'] = 'index.html?file=' + file['_path'];

					// var div = window.webshark.webshark_create_file_details(file);

					// window.webshark.dom_set_child(document.getElementById('capture_files_view_details'), div);

					// stat.innerHTML = 'Uploaded:';
					stat.innerHTML = file.message;
				}
			}

			var formData = new FormData();
			formData.append('f', f, f.name);
			formData.append('_token', document.querySelector('meta[name=csrf-token').attributes.content.value);
			xhr.send(formData);

			ev.preventDefault();
			stat.innerHTML = 'Uploading: ' + f.name;

			var upl = document.getElementById('uploader');
			upl.style.display = 'none';
		}

		function load()
		{
			var upl = document.getElementById('uploader');
			upl.onsubmit = form_upload;

			window.webshark.webshark_json_get(
				{
					req: 'info'
				},
				function(data)
				{
					document.title = document.title.replace("${WEBSHARK_VER}", data['version']);
					if (data['user'])
					{
						document.getElementById("publicnote").style.display = "none";
					}
					document.getElementById("ws_div").style.display = "block";
				});
		}

	</script>
</head>

<body onload="load()"><div id="ws_div" style="display: none;">
	<!-- <h2 id="publicnote">Uploaded files will be public! You need to login for private uploads.</h2> -->

	<p id='status'>Select capture file to upload.</p>

	<form id='uploader' action="{{ route('webshark.upload') }}" method="post" enctype="multipart/form-data">
	    <input id='f' type="file" name="f">
	    <input type="submit" value="Upload" name="submit">
	</form>

	<div id="capture_files_view_details">
	</div>

</div></body>
</html>
