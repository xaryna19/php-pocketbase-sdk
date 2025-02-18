<?php

namespace Pb;

/**
 *
 */
class Collection
{
    /**
     * @var string
     */
    private string $collection;

    /**
     * @var string
     */
    private string $url;

    /**
     * @var string
     */
    private static string $token = '';

    /**
     * @var bool
     */
    private bool $validator;

    /**
     * @param string $url
     * @param string $collection
     */
    public function __construct(string $url, string $collection, bool $validator = false)
    {
        $this->url = $url;
        $this->collection = $collection;
        $this->validator = $validator;
    }

    /**
     * @param int $start
     * @param int $end
     * @param array $queryParams
     * @return array
     */
    public function getList(int $start = 1, int $end = 50, array $queryParams = []): array
    {
        $queryParams['perPage'] = $end;
        $getParams = !empty($queryParams) ? http_build_query($queryParams) : "";
        $response = $this->doRequest($this->url . "/api/collections/" . $this->collection . "/records?" . $getParams, 'GET');

        return json_decode($response, JSON_FORCE_OBJECT);
    }

    /**
     * @param string $recordId
     * @param string $field
     * @param string $filepath
     * @return void
     */
    public function upload(string $recordId, string $field, string $filepath): void
    {
        $ch = curl_init($this->url . "/api/collections/".$this->collection."/records/" . $recordId);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS => array(
                $field => new \CURLFile($filepath)
            )
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $headers = array('Content-Type: multipart/form-data');

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        // var_dump($response);
    }

    /**
     * @param int $batch
     * @param array $queryParams
     * @return array
     */
    public function getFullList(int $batch = 200, array $queryParams = []): array
    {
        $queryParams = [... $queryParams, ['perPage' => $batch]];
        $getParams = !empty($queryParams) ? http_build_query($queryParams) : "";
        $response = $this->doRequest($this->url . "/api/collections/" . $this->collection . "/records?" . $getParams, 'GET');

        return json_decode($response, JSON_FORCE_OBJECT);
    }

    /**
     * @param string $filter
     * @param array $queryParams
     * @return array
     */
    public function getFirstListItem(string $filter, array $queryParams = []): array
    {
        $queryParams['perPage'] = 1;
        $getParams = !empty($queryParams) ? http_build_query($queryParams) : "";
        $response = $this->doRequest($this->url . "/api/collections/" . $this->collection . "/records?" . $getParams, 'GET');
        return json_decode($response, JSON_FORCE_OBJECT)['items'][0];
    }

    /**
     * @param array $bodyParams
     * @param array $queryParams
     * @return void
     */
    public function create(array $bodyParams = [], array $queryParams = []): string
    {
        return $this->doRequest($this->url . "/api/collections/" . $this->collection . "/records", 'POST', json_encode($bodyParams));
    }

    /**
     * @param string $recordId
     * @param array $bodyParams
     * @param array $queryParams
     * @return void
     */
    public function update(string $recordId, array $bodyParams = [], array $queryParams = []): void
    {
        // Todo bodyParams equals json, currently workaround
        $this->doRequest($this->url . "/api/collections/" . $this->collection . "/records/" . $recordId, 'PATCH', json_encode($bodyParams));
    }

    /**
     * @param string $recordId
     * @param array $queryParams
     * @return void
     */
    public function delete(string $recordId, array $queryParams = []): void
    {
        $this->doRequest($this->url . "/api/collections/" . $this->collection . "/records/" . $recordId, 'DELETE');
    }

    /**
     * @param string $recordId
     * @param string $url
     * @param string $method
     * @return bool|string
     */
    public function doRequest(string $url, string $method, $bodyParams = []): string
    {
        $authPB = new Client($this->url);
        if( $authPB->authValSession() )
        {
            $ch = curl_init();
            if ( isset( $_SESSION['_pb_tokenize'] ) ) {
                $headers = array(
                    'Content-Type:application/json',
                    'Authorization: ' . $_SESSION['_pb_tokenize']['token']
                );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($bodyParams) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyParams);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            $output = curl_exec($ch);
            curl_close($ch);
            if( $this->validator ){ $_SESSION['_pb_tokenize']['isValid'] = true ; }
        }else{
            $output = '{"code":404,"message":"The requested resource was not found.","data":{}}';
            if( $this->validator ){ $_SESSION['_pb_tokenize']['isValid'] = false ; }
        }
        return $output;
    }

    /**
     * @param string $recordId
     * @param array $queryParams
     * @return mixed
     */
    public function getOne(string $recordId, array $queryParams = []): array
    {
        $getParams = !empty($queryParams) ? http_build_query($queryParams) : "";
        $output = $this->doRequest($this->url . "/api/collections/" . $this->collection . "/records/" . $recordId . "?" . $getParams, 'GET');
        return json_decode($output, JSON_FORCE_OBJECT);
    }

}
