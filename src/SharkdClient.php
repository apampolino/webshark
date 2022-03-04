<?php

namespace Apampolino\Webshark;

use Apampolino\Webshark\Contracts\WiresharkClientInterface;

class SharkdClient implements WiresharkClientInterface {

    protected $socket;
    protected $host;
    protected $port;
    protected $connected;
    protected $buffer;

    public function __construct($host, $port)
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('tcp'));
        $this->host = $host;
        $this->port = $port;
    }

    public function init($recv_to = null, $send_to = null)
    {   
        $recv_to = $recv_to ?? array('sec'=> 0, 'usec'=> 100);
        $send_to = $send_to ?? array('sec'=> 0, 'usec'=> 100);
        // set timeout for response
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $recv_to);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, $send_to);
        $this->connected = socket_connect($this->socket, $this->host, $this->port);
    }

    public function connected()
    {
        return $this->connected;
    }

    public function getSocket()
    {
        return $this->socket;
    }

    public function close()
    {
        socket_close($this->socket);
    }

    private function send_raw($buf)
    {
        $sent = socket_send($this->socket, $buf, strlen($buf), MSG_EOR);
    }

    private function recv()
    {
        while (true) {
            // $bytes = socket_recv($this->socket, $buf, 8192, 0);

            // if ($bytes === FALSE || $bytes == 0) {
            //     break;
            // }

            // $this->buffer[] = preg_replace("/\n/", "", $buf);

            $bytes = socket_read($this->socket, 8192);

            if (strlen($bytes) == 0) {
                break;
            }

            $this->buffer[] = preg_replace("/\n/", "", $bytes);
        }
    }

    protected function format($data)
    {
        return json_encode($data) . "\n";
    }

    public function send($data) {

        try {
            $this->buffer = [];
            $data = $this->format($data);
            $this->send_raw($data);
            $this->recv();
            return implode('', $this->buffer);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}