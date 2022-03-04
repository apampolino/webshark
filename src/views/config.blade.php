<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">

	<title>${CAPTURE_FILE} configuration - webshark</title>

	<link rel="stylesheet" href="{{ asset('vendor/webshark/static/css/webshark.css') }}" type="text/css">
	<link rel="stylesheet" href="{{ asset('vendor/webshark/static/css/awesomplete.css') }}" type="text/css">

	<script src="{{ asset('vendor/webshark/static/js/webshark-app.js') }}" type="text/javascript"></script>

	<script type="text/javascript">
		var g_webshark_url = "{{ route('webshark.json') }}";

		var g_webshark_preferences = null;

		function run_option_filter(el)
		{
			g_webshark_preferences.setPrefFilter(el.value);
		}

		function load()
		{
			var opt = window.webshark.webshark_get_params_url();

			var webshark_file = opt['file'];

			g_webshark_preferences = new window.webshark.WSPreferencesTable({
				filename: webshark_file,
				scrollId: 'options_view',
				contentId: 'options_items'
			});

			g_webshark_preferences.loadPrefs();

			/* TODO: column edit */

			document.title = document.title.replace("${CAPTURE_FILE}", webshark_file);
		}

	</script>
</head>

<body onload="load()"><div id="ws_div">
	<div>
		<input id="option_filter" type='text' placeholder="Apply an option filter" oninput='run_option_filter(this)' style='width: 600px;' />
	</div>

	<div id="options_view" style='height: 750px; overflow: auto; border: 2px solid grey;'>
		<table id="options_list" style="width: 100%;">
			<thead id="options_header">
				<tr>
					<th>Name</th>
					<th>Type</th>
					<th>Value</th>
				</tr>
			</thead>
			<tbody id="options_items">
			</tbody>
		</table>
	</div>
</div></body>
</html>
