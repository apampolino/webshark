<?php

namespace Apampolino\Webshark\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Apampolino\Webshark\Contracts\WiresharkClientInterface;

class WebsharkController extends Controller
{
    protected $webshark;
    protected $storage;
    protected $storage_driver;
    protected $cap_dir;
    protected $upload_dir;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(WiresharkClientInterface $webshark)
    {
        $this->webshark = $webshark;
        $this->storage_driver = env('WEBSHARK_STORAGE_DRIVER', 'caps');
        $this->cap_dir = env('WEBSHARK_CAP_DIR', '');
        $this->upload_dir = env('WEBSHARK_UPLOAD_DIR', 'uploads');
        $this->storage = Storage::disk($this->storage_driver);
    }

    public function index(Request $request)
    {
        return view('webshark::index', []);
    }

    public function json(Request $request)
    {
        switch ($request->req) {
            case 'files':
                $files = $this->storage->files($this->cap_dir);
                
                $res = ['files' => [], 'pwd' => '.'];

                foreach ($files as $file) {
                    array_push($res['files'], ['name' => $file, 'size' => $this->storage->size($file)]);
                }

                break;
            default:

                if ($request->req == 'dumpconf') {
                    $this->webshark->init(['sec' => 1, 'usec' => 1000], ['sec' => 1, 'usec' => 1000]);
                } else {
                    // $this->webshark->init(['sec' => 2, 'usec' => 2000]);
                    $this->webshark->init();
                }

                if ($request->has('capture')) {
                    $this->webshark->send(['req' => 'load', 'file' => storage_path($this->storage_driver) . $request->capture]);
                }

                $res = preg_replace("/{\"err\":\d+}/", "", $this->webshark->send($request->all()));
                $this->webshark->close();
                break;
        }

        return response($res);
    }

    public function showUpload(Request $request)
    {
        return view('webshark::upload', []);
    }

    public function upload(Request $request)
    {
        if ($request->file('f')->isValid()) {
            $orig_name = explode('.', $request->f->getClientOriginalName());
            $filename = reset($orig_name) . '_' . date('YmdHis', strtotime('now')) . '.' . $request->f->extension();
            $request->f->storeAs($this->upload_dir, $filename, $this->storage_driver);
            $message['message'] = 'Successfully uploaded: ' . $request->f->getClientOriginalName();

            if ($request->f->extension() != 'pcap') {
                $message['err'] = 0;
            }
        }
        return response($message);
    }

    public function download(Request $request)
    {
        // todo
        // return response($message);
    }

    public function showConfig(Request $request)
    {
        return view('webshark::config', []);
    }
}
