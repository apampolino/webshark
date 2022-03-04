<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>webshark</title>

    <link rel="stylesheet" href="{{ asset('vendor/webshark/static/css/webshark.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('vendor/webshark/static/css/awesomplete.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('vendor/webshark/static/css/c3.min.css') }}" type="text/css"></link>

    <script src="{{ asset('vendor/webshark/static/js/d3.v4.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendor/webshark/static/js/wavesurfer.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendor/webshark/static/js/c3.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('vendor/webshark/static/js/webshark-app.js') }}" type="text/javascript"></script>

    <script type="text/javascript">
        var g_webshark_file = "";
        var g_webshark_url = '/webshark/json';
        var g_webshark_on_frame_change = null;
        var g_webshark_prototree_html = null;
        var g_webshark_hexdump = null;
        var g_webshark = null;
        var g_webshark_packet_list = null;
        var g_webshark_interval = null;
        var g_webshark_iograph = null;

        var g_webshark_files = null;
        var g_webshark_display_filter = null;

        var g_ws_ftypes = [ ];
        var g_ws_taps  = [ ];
        var g_ws_stats = [ ];
        var g_ws_nstat = [ ];
        var g_ws_eo    = [ ];
        var g_ws_follow= [ ];
        var g_ws_seqa  = [ ];
        var g_ws_srt   = [ ];
        var g_ws_rtd   = [ ];
        var g_ws_convs = [ ];

        var g_ws_pref = { };

        var g_ws_default_pref =
        {
            columns_width:
            {
                '%m': 1.7 * 59,
                '%t': 1.7 * 94,
                '%s': 1.7 * 154,
                '%d': 1.7 * 154,
                '%p': 1.7 * 56,
                '%L': 1.7 * 48,
                // '%i'
            },

            columns:
            [
                { "No.":          "%m" },
                { "Time":         "%t" },
                { "Source":       "%s" },
                { "Destination":  "%d" },
                { "Protocol":     "%p" },
                { "Length":       "%L" },
                { "Info":         "%i" },
//              { "tcp.stream": "tcp.stream:0" },
            ],

            layout_optimize_height: true
        };

        function setup_user_toolbar(user)
        {
            if (user)
            {
                var el = document.getElementById('user_login');
                el.innerHTML = user;
                el.style.display = 'inline';

                document.getElementById('user_registered').style.display = "inline";
            }
            else
            {
                document.getElementById('user_anon').style.display = "inline";
            }
        }

        function setup_frame_toolbar()
        {
            document.getElementById('toolbar_frame').style.display = 'flex';

            var comment_a = document.getElementById('toolbar_frame_comment');
            var timeref_a = document.getElementById('toolbar_frame_timeref');

            /* Edit comment */
            {
                comment_a.addEventListener("click", window.webshark.webshark_frame_comment_on_click);
                comment_a.addEventListener("mouseover", window.webshark.webshark_frame_comment_on_over);

                var glyph = window.webshark.webshark_glyph_img('comment', 32);
                glyph.setAttribute('alt', 'Comment');
                glyph.setAttribute('title', 'Comment');
                window.webshark.dom_set_child(comment_a, glyph);
            }

            /* Set time reference */
            {
                timeref_a.addEventListener("click", window.webshark.webshark_frame_timeref_on_click);

                var glyph = window.webshark.webshark_glyph_img('timeref', 32);
                glyph.setAttribute('alt', 'Time reference');
                glyph.setAttribute('title', 'Time reference');
                window.webshark.dom_set_child(timeref_a, glyph);
            }

            g_webshark_on_frame_change = function (framenum, data)
            {
                comment_a['data_ws_frame'] = framenum;
                timeref_a['data_ws_frame'] = framenum;
            };
        }

        function create_tap_menu(id, taps)
        {
            var node = document.getElementById(id);

            for (var i = 0; i < taps.length; i++)
            {
                var li = document.createElement("li");
                var a = document.createElement("a");

                var title = taps[i].name;
                var tap = taps[i].tap;

/*
TODO: Give user possible to generate multiple reports by selecting checkboxes
                var inp = document.createElement('input');
                inp.setAttribute('type', 'checkbox');
                a.appendChild(inp);
 */

                a.appendChild(document.createTextNode(title));
                a.setAttribute("target", "_blank");
                a.setAttribute("href", window.webshark.webshark_create_url(
                    {
                        file: g_webshark_file,
                        tap: tap
                    }));

                a.setAttribute("id", "menu_tap_" + tap);
                // a.addEventListener("click", window.webshark.popup_on_click);

                li.appendChild(a);
                node.appendChild(li);
            }
        }

        function set_filter(filter)
        {
            g_webshark.setFilter(filter);
        }

        function filter_files(filter)
        {
            g_webshark_files.setFilesFilter(filter);
        }

        function set_field_filter(filter)
        {
            g_webshark_prototree_html.setFieldFilter(filter);
        }

        function run()
        {
            var opt = window.webshark.webshark_get_params_url();

            g_webshark_file = opt['file'];

            var frame = opt['frame'];
            var tap = opt['tap'];
            var follow = opt['follow'];
            var filter = opt['filter'];
            var dir = opt['dir'];

            var g_webshark_display_filter = new window.webshark.WSDisplayFilter({
                contentId: "display_filter"
            });

            if (follow) {

                document.title = "Following " + follow + ' (' + filter + ') ' + g_webshark_file + " - " + document.title;

                document.getElementById('toolbar_capture').style.display = 'none';
                document.getElementById('ws_packet_list_view').style.display = 'none';
                document.getElementById('ws_packet_detail_view').style.display = 'none';
                document.getElementById('ws_packet_bytes_view').style.display = 'none';

                document.getElementById('toolbar_tap').style.display = 'block';

                window.webshark.webshark_load_follow(follow, filter);

            } else if (tap) {
                document.title = "Report of " + g_webshark_file + " - " + document.title;

                document.getElementById('toolbar_capture').style.display = 'none';
                document.getElementById('ws_packet_list_view').style.display = 'none';
                document.getElementById('ws_packet_detail_view').style.display = 'none';
                document.getElementById('ws_packet_bytes_view').style.display = 'none';

                document.getElementById('toolbar_tap').style.display = 'block';

                window.webshark.webshark_load_tap(tap.split(";"));

            } else if (frame) {
                document.title = g_webshark_file + " #" + frame + " - " + document.title;

                window.webshark.webshark_load_frame(frame, true);

                document.getElementById('toolbar_capture').style.display = 'none';
                document.getElementById('ws_packet_list_view').style.display = 'none';

                setup_frame_toolbar();

            } else if (g_webshark_file) {
                var new_title = g_webshark_file + " - " + document.title;

                document.title = "Loading: " + new_title;

                setup_frame_toolbar();

                if (g_ws_pref['layout_optimize_height'] == true)
                {
                    document.getElementById('ws_packet_detail_view').style.width = '57%';
                    document.getElementById('ws_packet_bytes_view').style.width = '42%';
                    document.getElementById('ws_packet_bytes_view').style.height =
                        document.getElementById('ws_packet_detail_view').style.height;
                }

                g_webshark.load(g_webshark_file, function(data)
                {
                document.title = new_title;

                if (filter)
                {
                    g_webshark_display_filter.setFilter(filter);
                }

                /* Back to file list */
                {
                    var back_a = document.getElementById('toolbar_capture_files');
                    back_a.setAttribute("href", "{{ route('webshark.index') }}");

                    var glyph = window.webshark.webshark_glyph_img('files', 16);
                    glyph.setAttribute('alt', 'Back to file list');
                    glyph.setAttribute('title', 'Back to file list');
                    window.webshark.dom_set_child(back_a, glyph);
                }

                /* Link to download capture file */
                {
                    var down_a = document.getElementById('toolbar_capture_down');
                    down_a.setAttribute("target", "_blank");
                    down_a.setAttribute("href", window.webshark.webshark_create_api_url(
                        {
                            req: 'download',
                            capture: g_webshark_file,
                            token: 'self'
                        }));
                    down_a.addEventListener("click", window.webshark.popup_on_click_a);

                    var glyph = window.webshark.webshark_glyph_img('download', 16);
                    glyph.setAttribute('alt', 'Download capture file');
                    glyph.setAttribute('title', 'Download capture file');
                    window.webshark.dom_set_child(down_a, glyph);
                }

                /* Capture file description */
                {
                    var label_span = document.getElementById('toolbar_capture_description');
                    var label_str = " " + data['filename'] + " (" + data['frames'] + " frames, " + data['duration'] + ' seconds, ' + data['filesize'] + " bytes)";
                    window.webshark.dom_set_child(label_span, document.createTextNode(label_str));
                }

                /* Capture file settings */
                {
                    var pref_a = document.getElementById('toolbar_capture_settings');
                    pref_a.setAttribute("target", "_blank");
                    pref_a.setAttribute("href", "{{ route('webshark.config.show', ['capture_file' => 'item']) }}".replace('item', g_webshark_file.replace(/\//, '')));
                    pref_a.addEventListener("click", window.webshark.popup_on_click_a);

                    var glyph = window.webshark.webshark_glyph_img('settings', 16);
                    glyph.setAttribute('alt', 'Configure capture file');
                    glyph.setAttribute('title', 'Configure capture file');
                    window.webshark.dom_set_child(pref_a, glyph);
                }

                /* TODO: link to download ssl-sessions  */

                if (opt['hide_menu'] == undefined)
                {
                    create_tap_menu('report_menu', g_ws_taps);
                    create_tap_menu('report_menu_conv', g_ws_convs);
                    create_tap_menu('report_menu_srt', g_ws_srt);
                    create_tap_menu('report_menu_srt', g_ws_rtd);
                    create_tap_menu('report_menu_stat', g_ws_stats);
                    create_tap_menu('report_menu_stat2', g_ws_nstat);
                    create_tap_menu('report_menu_eo', g_ws_eo);
                    create_tap_menu('report_menu', g_ws_follow);
                    create_tap_menu('report_menu', g_ws_seqa);
                }

                set_filter(filter);

                });

            } else {
                document.title = "Capture files - " + document.title;

                /* Link to upload capture file */
                {
                    var upload_a = document.createElement('a');
                    upload_a.setAttribute("target", "_blank");
                    upload_a.setAttribute("href", "{{ route('webshark.upload.show') }}");
                    upload_a.addEventListener("click", window.webshark.popup_on_click_a);

                    var glyph = window.webshark.webshark_glyph_img('upload', 16);
                    glyph.setAttribute('alt', 'Upload capture file');
                    glyph.setAttribute('title', 'Upload capture file');

                    upload_a.appendChild(glyph);

                    var toolbar = document.getElementById('files_view');
                    toolbar.insertBefore(upload_a, toolbar.childNodes[0]);
                }

                document.getElementById('toolbar_capture').style.display = 'none';
                document.getElementById('ws_packet_list_view').style.display = 'none';
                document.getElementById('ws_packet_detail_view').style.display = 'none';
                document.getElementById('ws_packet_bytes_view').style.display = 'none';

                document.getElementById('files_view').style.display = 'block';

                g_webshark_files.loadFiles(dir);
            }

            document.getElementById("ws_div").style.display = "block";
        }

        function load_preferences()
        {
            for (var key in g_ws_default_pref)
            {
                if (g_ws_pref[key] == undefined)
                    g_ws_pref[key] = g_ws_default_pref[key];
            }
        }

        function render_interval(new_mode)
        {
            g_webshark_interval.mode = new_mode;
            g_webshark_interval.render_interval();

            if (new_mode != 'fps')
                document.getElementById('capture_interval_fps').classList.remove('selected');
            if (new_mode != 'bps')
                document.getElementById('capture_interval_bps').classList.remove('selected');
            document.getElementById('capture_interval_' + new_mode).classList.add('selected');
        }

        function show_hide_graph(id, id2)
        {
            var elem = document.getElementById(id);
            var elem2 = document.getElementById(id2);

            if (elem.style['display'] != 'block')
            {
                elem.style['display'] = 'block';
                elem2.classList.add('selected');
            }
            else
            {
                elem.style['display'] = "none";
                elem2.classList.remove('selected');
            }
        }

        function add_graph()
        {
            g_webshark_iograph.addGraph();
        }

        function render_graph()
        {
            g_webshark_iograph.update();
        }

        function bytes_display_base(new_base)
        {
            g_webshark_hexdump.base = new_base;
            g_webshark_hexdump.render_hexdump();

            if (new_base != 2)
                document.getElementById('packet_display_base_2').classList.remove('selected');
            if (new_base != 16)
                document.getElementById('packet_display_base_16').classList.remove('selected');
            document.getElementById('packet_display_base_' + new_base).classList.add('selected');
        }

        function show_hide_menu(id, id2)
        {
            var list = [ 'report_menu', 'report_menu_conv', 'report_menu_srt', 'report_menu_stat', 'report_menu_stat2', 'report_menu_eo' ];
            var elem;

            for (var i = 0; i < list.length; i++)
            {
                if (list[i] != id && list[i] != id2)
                {
                    elem = document.getElementById(list[i]);
                    elem.style['display'] = 'none';
                }
            }

            elem = document.getElementById(id);
            elem.style['display'] = (elem.style['display'] != 'block') ? 'block' : "none";
            if (id2 != undefined)
            {
                elem = document.getElementById(id2);
                elem.style['display'] = (elem.style['display'] != 'block') ? 'block' : "none";
            }
        }

        function load()
        {
            g_webshark_files = new window.webshark.WSCaptureFilesTable({
                scrollId: 'capture_files_view',
                contentId: 'capture_files',
                fileDetailsId: 'capture_files_view_details'
            });

            g_webshark_interval = new window.webshark.WSInterval({
                contentId: 'capture_interval',
                descrId: 'capture_interval_descr',
                mode: "bps",
                width: 620 /* number of probes - currently size of svg */
            });

            g_webshark_iograph = new window.webshark.WSIOGraph({
                contentId: 'capture_graph',
                tableId: 'capture_graph_table'
            });

            g_webshark_iograph.addGraph();

            g_webshark_packet_list = new window.webshark.WSPacketList({
                headerId: 'packet_list_header',
                headerFakeId: 'packet_list_header_fake',
                scrollId: 'ws_packet_list_view_scroll',
                contentId: 'packet_list_frames'
            });

            g_webshark_prototree_html = new window.webshark.ProtocolTree({
                contentId: 'ws_packet_detail_view'
            });

            g_webshark_hexdump = new window.webshark.WSHexdump({
                base: 16,
                tabsId: 'ws_packet_pane',
                contentId: 'ws_bytes_dump'
            });

            load_preferences();

            g_webshark = new window.webshark.Webshark();

            window.webshark.webshark_json_get(
                {
                    req: 'info'
                },
                function(data)
                {
                    g_ws_taps  = data['taps'];
                    g_ws_stats = data['stats'];
                    g_ws_nstat = data['nstat'];
                    g_ws_eo    = data['eo'];
                    g_ws_follow= data['follow'];
                    g_ws_seqa  = data['seqa'];
                    g_ws_srt   = data['srt'];
                    g_ws_rtd   = data['rtd'];
                    g_ws_convs = data['convs'];
                    g_ws_ftypes = data['ftypes'];

                    var columns_default = [ "%m", "%t", "%s", "%d", "%p", "%L", "%i" ];
                    var columns_title   = [ ];
                    var columns_width   = [ ];
                    var columns_pref    = [ ];

                    // document.title = document.title.replace("${WEBSHARK_VER}", data['version']);

                    // setup_user_toolbar(data['user']);

                    for (var col = 0; col < g_ws_pref.columns.length; col++)
                    {
                        var col_title = "";
                        var col_format = "";
                        var col_format_idx = -1;

                        for (col_title in g_ws_pref.columns[col]) { col_format = g_ws_pref.columns[col][col_title]; }
                        columns_title.push(col_title);
                        columns_width.push(g_ws_pref.columns_width[col_format]);

                        if (col >= columns_default.length || columns_default[col] != col_format)
                        {
                            g_webshark.setColumns(columns_pref);
                        }

                        /* map from column format to integer */
                        var ws_d_columns = data['columns'];
                        for (var col_map = 0; col_map < ws_d_columns.length; col_map++)
                        {
                            if (ws_d_columns[col_map].format == col_format)
                            {
                                col_format_idx = col_map;
                            }
                        }

                        if (col_format_idx != -1)
                            columns_pref.push(col_format_idx);
                        else
                            columns_pref.push(col_format);
                    }

                    g_webshark_packet_list.setColumns(columns_title, columns_width);
                    run();
                });
        }

    </script>
</head>

<body onload="load()"><div id="ws_div" style="display: none;">
    <div id="user_toolbar" style="display:none;">
        <div style="float: right; margin-right: 5px;">
            <span id="user_anon" style="display: none;">
                Guest
                <a href="/login">Login</a>
            </span>
            <span id="user_registered" style="display: none;">
                <span id="user_login"></span>
                <a href="/logout/?next=/webshark">Logout</a>
            </span>
        </div>
    </div>

    <div id="files_view" style="display: none;">
        <input id="files_filter" placeholder="Apply a files filter" type='text' onchange='filter_files(this.value);' style='width: 1000px;' />
        <p style="font-size: 12px;">Filter examples:
            <span class="example">filename</span>
            <span class="example">duration&lt;0.5 frames&gt;42</span>
            <span class="example">duration&gt;60 frames&lt;42</span>
            <span class="example">proto:tcp</span>
            <span class="example">proto:wlan proto:eap</span>
        </p>
        <div id="capture_files_view" style="max-height: 800px; overflow: auto; width: 70%; float: left;">
            <table style="width: 100%;">
                <thead id="capture_files_header">
                    <tr>
                        <th>Filename</th>
                        <th>File Size</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody id="capture_files">
                </tbody>
            </table>
        </div>
        <div id="capture_files_view_details" style="max-height:700px; overflow: auto; width: 20%; float: left;">
            Click file to get details
        </div>
    </div>

    <div id="toolbar_capture">
        <div style="float: left;">
            <a id="toolbar_capture_files"></a>
            <a id="toolbar_capture_settings"></a>
            <span id="toolbar_capture_description"></span>
            <a id="toolbar_capture_down"></a>
            <p />
            <input id="display_filter" placeholder="Apply a display filter" type='text' onchange='set_filter(this.value);' style='width: 550px;' />

            <ul class="menul">
            <li>
                <a onclick="show_hide_menu('report_menu_conv');">Endpoints
                    <div class="submenu" id="report_menu_conv"></div>
                </a>
                <a onclick="show_hide_menu('report_menu_srt');">Response Time
                    <div class="submenu" id="report_menu_srt"></div>
                </a>
                <a onclick="show_hide_menu('report_menu_stat', 'report_menu_stat2');">Statistics
                    <div class="submenu" id="report_menu_stat" style="margin-left: -200px;"></div>
                    <div class="submenu" id="report_menu_stat2" style="margin-left: 200px;"></div>
                </a>
                <a onclick="show_hide_menu('report_menu_eo');">Export Objects
                    <div class="submenu" id="report_menu_eo"></div>
                </a>
                <a onclick="show_hide_menu('report_menu');">Misc
                    <div class="submenu" id="report_menu"></div>
                </a>
            </li>
        </ul>
        </div>
        <div style="float: right; border: 1px solid grey;">
            <div id="capture_interval" style="margin-top: 2px;">
                <svg width="620" height="100"></svg>
            </div>
            <div style="float: left;">
                <span style="font-size: 12px;" id="capture_interval_descr"><!---Displaying XXX frames out of YYY.--></span>
            </div>
            <div style="float: right;">
                <button id="capture_interval_bps" class="wsbutton selected" onclick="render_interval('bps')">Bytes</button>
                <button id="capture_interval_fps" class="wsbutton" onclick="render_interval('fps')">Frames</button>
                <button id="capture_interval_adv" class="wsbutton" style="margin-left: 15px;" onclick="show_hide_graph('report_graph', 'capture_interval_adv')">Advanced Graph</button>
            </div>
        </div>
        <div style="clear: both;"></div>

        <div id="report_graph" style="border: 2px solid grey; display: none;">
            <h3 style="text-align: center;">Advanced graph</h3>
            <div id="capture_graph">
                <svg width="620" height="300"></svg>
            </div>
            <div>
                <button class="wsbutton" onclick="add_graph()">Add Graph</button>
                <button class="wsbutton" style="width: 200px; margin-left: 10px; background-color: lightblue;" onclick="render_graph()">Render</button>
            </div>
            <table class="packet_list" style="width: 100%;">
                <thead>
                    <tr>
                        <th width="20%">Name</th>
                        <th width="30%">Display filter</th>
                        <th width="5%">Color</th>
                        <th width="10%">Style</th>
                        <th width="10%">Y Axis</th>
                        <th width="25%">Y Field</th>
                    </tr>
                </thead>
                <tbody id="capture_graph_table">
                </tbody>
            </table>
        </div>
    </div>

    <div id="toolbar_tap" style="display: none;">
        Placeholder for taps toolbar
        <div id="ws_tap_graph" style="width: 2000px; overflow-x: auto;"></div>
        <div id="ws_tap_extra" style="display: none; height: 700px;"></div>
        <div id="ws_tap_table" style="max-height: 550px; overflow-y: auto; border: 2px solid grey;"></div>
        <div id="ws_tap_details" style="display: none; max-height: 500px; overflow-y: auto;"></div>
    </div>

    <div id="ws_packet_list_view" style='border: 2px solid grey; margin-top: 12px;'>
        <div id="ws_packet_list_view_scrollbar_fixer" style="margin-right: 15px;">
            <table class="packet_list" style="width: 100%; table-layout: fixed;">
                <thead id="packet_list_header">
                    <!-- <tr><th>No.</th></tr> -->
                </thead>
            </table>
        </div>
        <div id="ws_packet_list_view_scroll" style="height: 330px; overflow-y: auto; overflow-x: hidden;">
            <table class="packet_list" style="width: 100%; table-layout: fixed;">
                <thead id="packet_list_header_fake"></thead>
                <tbody id="packet_list_frames">
                    <!--- <tr><td>1</td><td>2h</td></tr> -->
                </tbody>
            </table>
        </div>
    </div>

    <div id="toolbar_frame" style="display: none; margin:12px;">
        <a id="toolbar_frame_timeref"></a>
        <a id="toolbar_frame_comment" style="margin-left: 5px;"></a>
        <input id="field_filter" placeholder="Apply a field filter" type='text' onchange='set_field_filter(this.value);' style='width: 350px; margin-left: 5px;' />
    </div>

    <div id="ws_packet_detail_view" style='overflow: auto; width: 100%; height: 250px; border: 2px solid grey; float: left;'>
Select frame
    </div>

    <div id="ws_packet_bytes_view" style='overflow: auto; width: 100%; height: 200px; border: 2px solid grey; float: right;'>
        <div id="ws_packet_pane" style="float: left;"></div>
        <div style="float: right;">
            <button id="packet_display_base_16" class="wsbutton selected" onclick="bytes_display_base(16)">Hex</button>
            <button id="packet_display_base_2"  class="wsbutton" onclick="bytes_display_base(2)">Bits</button>
        </div>
        <div style="clear: both;"></div>
        <pre id='ws_bytes_dump'>Select frame</pre>
    </div>

</div></body>
</html>
