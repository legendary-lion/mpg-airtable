<?php
	$response = array(
		"error" => false,
		"msg"	=> "success",
		"log" 	=> "",
        "data" => [],
	);
    try {
        require 'vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    // =====================================
    // * DEFINE TIMESTAMPS FOR APIS GET QUERY AND POST VALUES
    // =====================================
        ob_start();

        $datetime = $_GET['datetime'];
        $live = ($_GET['live']) ? $_GET['live'] : 0;
        $report = ($_GET['report']) ? $_GET['report'] : 0;

        $query_date_qubica = gmdate('Y-m-d', strtotime("-1 days"));
        $query_date_intercard = date('Ymd', strtotime("-1 days"));
        
        if ($datetime) {
            $user_datetime = new DateTime();
            $user_datetime->setTimestamp($datetime);
            $user_datetime->setTimezone(new DateTimeZone('America/Detroit'));
            $query_date_qubica = $user_datetime->format('Y-m-d');
            $query_date_intercard = $user_datetime->format('Ymd');
        }

        $now_datetime = new DateTime("now");
        $now_datetime->setTimezone(new DateTimeZone('America/Detroit'));
        $timestamp = $now_datetime->format('Y-m-d H:i:s');

    // =====================================
    // * DEFINE GET FUNCTIONS
    // =====================================
        function write_to_log($log_type, $method, $response, $url) {
            $type = strtoupper($log_type);
            $action = 'modified on';
            switch ($method) {
                case 'GET':
                    $action = 'retrieved from';
                    break;
                case 'POST':
                    $action = 'added to';
                    break;
                case 'DELETE':
                    $action = 'removed from';
                default:
                    # code...
                    break;
            }
            if ($log_type == 'success') {
                $response_arr = json_decode($response, true);
                $record_count = (is_array($response_arr) && array_key_exists('records', $response_arr)) ? count($response_arr['records']) . ' record(s)' : 0;
                $record_msg = ($record_count) ? $record_count : "Data";
                $msg = $type . ": $method : $record_msg $action $url";
            }
            else {
                $msg = $type . ": $method : $response from $url";
            }
            $now_datetime = new DateTime("now");
            $now_datetime->setTimezone(new DateTimeZone('America/Detroit'));
            $timestamp = $now_datetime->format('Y-m-d H:i:s');
            $entry = "$timestamp | $msg" . PHP_EOL;
            echo $entry;
        }
        
        function get_qubica_data($query_date) {
            $url = "https://centerapi.qubicaamf.com/rest/6444/reporting/economicalhistory/shifts?DateFrom=". $query_date ."T00:00:00&DateTo=". $query_date ."T23:59:59&Level=AllData";
            $account_name = "LegendaryLion";
            $account_key = $_ENV['QUBICA_ACCOUNT_KEY'];
            $request_method = "GET";
            $request_path = "/rest/6444/reporting/economicalhistory/shifts";
            $time = time();
            $auth_date = gmdate("m/d/Y H:i:s", $time);
            $date_header = gmdate("D, d M Y H:i:s T", $time);
            $auth_message = $request_method . "\n" . $auth_date . "\n" . $request_path;
            $auth_header = "CqApiAuth " . $account_name . ":" . base64_encode(hash_hmac("sha256", $auth_message, $account_key, true));
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: $auth_header",
                    "X-ApiAuth-Date: $date_header"
                ),
            ));
            $c_response = curl_exec($curl);
            if (curl_errno($curl)) {
                $err_msg = curl_error($curl);
                write_to_log("error", "GET", "Request failed with message: $err_msg", $url);
                curl_close($curl);
            } 
            else {
                $result_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($result_status == 200) {
                    write_to_log('success', "GET", $response, $url);
                    curl_close($curl);
                } 
                else {
                    write_to_log('error', "GET", "Request failed: HTTP status code: $result_status", $url);
                    curl_close($curl);
                }
            }
            $response_data = $c_response;
            return $response_data;
        }

        function get_intercard_data($endpoint, $query_date) {
            $key = $_ENV['INTERCARD_API_KEY'];
            $url = "https://perfectgame.icardinc.net/WS_RevenueExtract/RestService/$endpoint?macid=$key&startdate=" . $query_date . "&enddate=" . $query_date;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));
            $c_response = curl_exec($curl);
            $response_data = preg_replace('/[\x00-\x1F\x80-\xFF]/', '',stripslashes(trim(preg_replace('/\s\s+/', '', $c_response))));
            if (curl_errno($curl)) {
                $err_msg = curl_error($curl);
                write_to_log("error", "GET", "Request failed with message: $err_msg", $url);
                curl_close($curl);
            } 
            else {
                $result_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($result_status == 200) {
                    write_to_log('success', "GET", $response_data, $url);
                    curl_close($curl);
                } 
                else {
                    write_to_log('error', "GET", "Request failed: HTTP status code: $result_status", $url);
                    curl_close($curl);
                }
            }
            return $response_data;
        }

    // =====================================
    // * DECODE API DATA
    // =====================================

        $data_qubica = json_decode(get_qubica_data($query_date_qubica), true);
        $data_game = json_decode(get_intercard_data('GetGameRevenue', $query_date_intercard), true);
        $data_teller = json_decode(get_intercard_data('GetTellerRevenue', $query_date_intercard), true);
        $data_pos = json_decode(get_intercard_data('GetPosRevenue', $query_date_intercard), true);

        $report_data = [];

    // =====================================
    // * STORE ALL DATA IN SINGLE ARRAY
    // =====================================

        $all_data = [
            'qubica' => $data_qubica,
            'game' => $data_game,
            'teller' => $data_teller,
            'pos' => $data_pos
        ];

        // Preserved in case we need to add all values to store total as single record

        // function add_all_values($array, $key) {
        //     if (!$key || !$array)  {
        //         return 0;
        //     }
        //     $total = 0;
        //     foreach ($array as $i => $item) {
        //         $total += $item[$key];
        //     }
        //     return $total;
        // }

    // =====================================
    // * FORMAT INTERCARD TELLER DATA
    // =====================================

        $intercard_teller = ['records' => []];
        foreach ($all_data['teller'] as $i => $teller_record) {
            $intercard_teller_record = [
                'import_time' => "" . $timestamp,
                'query_date' => "" . $query_date_qubica,
                'Location' => "" . $teller_record['Location'],
                'Teller' => "" . $teller_record['Teller'],
                'TransCount' => "" . $teller_record['TransCount'],
                'CashCredits' => "" . $teller_record['CashCredits'],
                'CreditCardRevenue' => "" . $teller_record['CreditCardRevenue'],
                'CashCreditBonus' => "" . $teller_record['CashCreditBonus'],
            ];
            $intercard_teller['records'][]['fields'] = $intercard_teller_record;
            if ($report) {
                if ($teller_record['Teller'] == 'Teller 1') {
                    $report_data['teller_1_rev'] += (float)$teller_record['CashCredits'] + (float)$teller_record['CreditCardRevenue'];
                }
                if ($teller_record['Teller'] == 'Teller 2') {
                    $report_data['teller_2_rev'] += (float)$teller_record['CashCredits'] + (float)$teller_record['CreditCardRevenue'];
                }     
            }
        }

        if ($report) {
            $report_data['total_teller_rev'] = $report_data['teller_1_rev'] + $report_data['teller_2_rev'];
        }

    // =====================================
    // * FORMAT INTERCARD POS DATA
    // =====================================

        $intercard_pos = ['records' => []];
        foreach ($all_data['pos'] as $i => $pos_record) {
            $intercard_pos_record = [
                'import_time' => "" . $timestamp,
                'query_date' => "" . $query_date_qubica,
                'Location' => "" . $pos_record['Location'],
                'PointsComped' => "" . $pos_record['PointsComped'],
                'PointsBorrowed' => "" . $pos_record['PointsBorrowed'],
                'PointsDebited' => "" . $pos_record['PointsDebited'],
                'TransCount' => "" . $pos_record['TransCount'],
            ];
            $intercard_pos['records'][]['fields'] = $intercard_pos_record;
        }

    // =====================================
    // * FORMAT INTERCARD GAME DATA
    // =====================================

        $intercard_game = ['records' => []];
        foreach ($all_data['game'] as $i => $game_record) {
            $intercard_game_record = [
                'import_time' => "" . $timestamp,
                'query_date' => "" . $query_date_qubica,
                'Tag' => "" . $game_record['Tag'],
                'Description' => "" . $game_record['Description'],
                'Location' => "" . $game_record['Location'],
                'Group' => "" . $game_record['Group'],
                'CashDebits' => "" . $game_record['CashDebits'],
                'CashDebitBonus' => "" . $game_record['CashDebitBonus'],
                'Points' => "" . $game_record['Points'],
                'StandardPlay' => "" . $game_record['StandardPlay'],
                'EMPPlays' => "" . $game_record['EMPPlays'],
                'TotalRevenue' => "" . $game_record['TotalRevenue'],
            ];
            $intercard_game['records'][]['fields'] = $intercard_game_record;
        }

    // =====================================
    // * FORMAT QUBICA DATA
    // =====================================
        $qubica_overview = ['records' => []];
        // $qubica_receipts = ['records' => []];
        $qubica_receipt_items = ['records' => []];
        foreach ($all_data['qubica'] as $i => $session) {

            // =====================================
            // * QUBICA OVERVIEW
            // =====================================

                $qubica_overview_record = [
                    'import_time' => "" . $timestamp,
                    'query_date' => "" . $query_date_qubica,
                    'Key' => "" . $session['Key'],
                    'Total' => "" . $session['Total'],
                    'Taxes' => "" . $session['Taxes'],
                    'IncludedTaxes' => "" . $session['IncludedTaxes'],
                    'TotalPlusTaxes' => "" . $session['TotalPlusTaxes'],
                    'Refund' => "" . $session['Refund'],
                    'CloseAmount' => "" . $session['CloseAmount'],
                    'UnderOver' => "" . $session['UnderOver'],
                    'Rounding' => "" . $session['Rounding'],
                ];
                $qubica_overview['records'][]['fields'] = $qubica_overview_record;

            foreach ($session['Receipts'] as $i => $receipt) {

                // =====================================
                // * QUBICA RECEIPTS
                // =====================================

                    // $qubica_receipts_record = [
                    //     'import_time' => "" . $timestamp,
                    //     'query_date' => "" . $query_date_qubica,
                    //     'PrintDateTime' => "" . $receipt['PrintDateTime'],
                    //     'TotalWithoutTaxes' => "" . $receipt['TotalWithoutTaxes'],
                    //     'TaxesTotal' => "" . $receipt['TaxesTotal'],
                    //     'TotalWithTaxes' => "" . $receipt['TotalWithTaxes'],
                    // ];
                    // $qubica_receipts['records'][]['fields'] = $qubica_receipts_record;

                foreach ($receipt['Rows'] as $i => $receipt_item) {

                    // =====================================
                    // * QUBICA RECEIPT ITEMS
                    // =====================================

                        $qubica_receipt_items_record = [
                            'import_time' => "" . $timestamp,
                            'query_date' => "" . $query_date_qubica,
                            'ReceiptPrintDateTime' => "" . $receipt['PrintDateTime'],
                            'PricekeyDescr' => "" . $receipt_item['PricekeyDescr'],
                            'SingleValue' => "" . $receipt_item['SingleValue'],
                            'Quantity' => "" . $receipt_item['Quantity'],
                            'TotalValue' => "" . $receipt_item['TotalValue'],
                            'MainDepartmentDescr' => "" . $receipt_item['MainDepartmentDescr'],
                            'SubDep1Descr' => "" . $receipt_item['SubDep1Descr'],
                            'SubDep2Descr' => "" . $receipt_item['SubDep2Descr'],
                        ];
                        $qubica_receipt_items['records'][]['fields'] = $qubica_receipt_items_record;
                }
            }
        }

    // =====================================
    // * SEND TO AIRTABLE
    // =====================================

        // Airtable only allows a maximum of 10 records to be posted at once, so we need to split this into chunks of 10

        function get_chunks($data) {
            $chunks = [['records' => []]];
            $chunk_i = 0;
            $record_count = 0;
            foreach ($data['records'] as $record_i => $record) {
                $chunks[$chunk_i]['records'][] = $record;
                $record_count++;
                if ($record_count == 10) {
                    $record_count = 0;
                    $chunk_i++;
                }
            }
            return $chunks;
        }

        function delete_airtable_data($data_chunk, $table) {
            $query = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query($data_chunk));
            $base_url = "https://api.airtable.com/v0/appQ4ECgVYtwyoIRf/$table";
            $d_url = "$base_url?$query";
            $d_curl = curl_init();
            $key = $_ENV['AIRTABLE_API_KEY'];
            curl_setopt_array($d_curl, array(
                CURLOPT_URL => $d_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $key",
                    "Cookie: brw=brwjXZGEd54DK4gFW"
                ),
            ));
            $response = curl_exec($d_curl);
            if (curl_errno($d_curl)) {
                $err_msg = curl_error($d_curl);
                write_to_log("error", "DELETE", "Request failed with message: $err_msg", $base_url);
                curl_close($d_curl);
            }
            else {
                $result_status = curl_getinfo($d_curl, CURLINFO_HTTP_CODE);
                if ($result_status == 200) {
                    write_to_log('success', "DELETE", $response, $base_url);
                    curl_close($d_curl);
                    usleep(500000);
                } 
                else {
                    write_to_log('error', "DELETE", "Request failed: HTTP status code: $result_status", $base_url);
                    curl_close($d_curl);
                }
            }
        }

        function get_airtable_data_by_date($query_date, $table, $offset = false) {
            $offset_param = ($offset) ? "&offset=$offset" : '';
            $url = "https://api.airtable.com/v0/appQ4ECgVYtwyoIRf/$table?fields%5B%5D=id&filterByFormula=query_date+%3D+%22$query_date%22$offset_param";
            $key = $_ENV['AIRTABLE_API_KEY'];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $key",
                    "Cookie: brw=brwjXZGEd54DK4gFW"
                ),
            ));
            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                $err_msg = curl_error($curl);
                write_to_log("error", "GET", "Request failed with message: $err_msg", $url);
                curl_close($curl);
                return;
            }
            else {
                $result_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($result_status == 200) {
                    write_to_log('success',"GET", $response, $url);
                    curl_close($curl);
                    return json_decode($response, true);
                }
                else {
                    write_to_log('error', "GET", "Request failed: HTTP status code: $result_status", $url);
                    curl_close($curl);
                    return;
                }
            }
        }

        function delete_airtable_data_by_date($query_date) {
            $tables = ['qubica_overview','qubica_receipt_items','intercard_teller','intercard_pos','intercard_game'];
            foreach ($tables as $i => $table) {
                $delete_data = [
                    'records' => []
                ];
                $response = false;
                $have_records = true;
                $offset = false;
                while ($have_records) {
                    $response = get_airtable_data_by_date($query_date, $table, $offset);
                    if (!$response || !$response['records']) {
                        $have_records = false;
                        break;
                    }
                    foreach ($response['records'] as $record_i => $record) {
                        $delete_data['records'][] = $record['id'];
                    }
                    if (!array_key_exists('offset', $response)) {
                        $have_records = false;
                        break;
                    }
                    $offset = $response['offset'];
                }
                if ($delete_data['records']) {
                    $delete_data_chunks = get_chunks($delete_data);
                    foreach ($delete_data_chunks as $d_i => $chunk) {
                        delete_airtable_data($chunk, $table);
                    }
                }
            }
        }

        function post_to_airtable($chunks, $table) {
            foreach ($chunks as $chunk_i => $data_arr) {
                $data = json_encode($data_arr);
                $url = "https://api.airtable.com/v0/appQ4ECgVYtwyoIRf/$table";
                $curl = curl_init();
                $key = $_ENV['AIRTABLE_API_KEY'];
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer $key",
                        "Content-Type: application/json",
                        "Cookie: brw=brwjXZGEd54DK4gFW"
                    ),
                ));
                $response = curl_exec($curl);
                if (curl_errno($curl)) {
                    $err_msg = curl_error($curl);
                    write_to_log("error", "POST", "Request failed with message: $err_msg", $url);
                    curl_close($curl);
                }
                else {
                    $result_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    if ($result_status == 200) {
                        write_to_log('success', "POST", $response, $url);
                        curl_close($curl);
                    }
                    else {
                        write_to_log('error', "POST", "Request failed: HTTP status code: $result_status", $url);
                        curl_close($curl);
                    }
                }
                // AIRTABLE HAS A RATE LIMIT OF 5 REQUESTS PER SECOND
                // WE WAIT .5 SECONDS AFTER EVERY CHUNK POST TO ADHERE TO THIS LIMIT
                usleep(500000);
            }
        }

        // =====================================
        // CALLS TO SEND DATA
        // =====================================

        if (!$report) {
            delete_airtable_data_by_date($query_date_qubica);
            if (!$live) {
                post_to_airtable(get_chunks($qubica_overview), 'qubica_overview');
                // post_to_airtable(get_chunks($qubica_receipts), 'qubica_receipts');
                post_to_airtable(get_chunks($qubica_receipt_items), 'qubica_receipt_items');
            }

            post_to_airtable(get_chunks($intercard_teller), 'intercard_teller');
            post_to_airtable(get_chunks($intercard_pos), 'intercard_pos');
            post_to_airtable(get_chunks($intercard_game), 'intercard_game');
        }

        function log_data($log_msg, $timestamp) {
            $log_foldername = "logs";
            if (!file_exists($log_foldername)) {
                mkdir($log_foldername, 0777, true);
            }
            $log_file_path = "$log_foldername/log_$timestamp.log";
            file_put_contents($log_file_path, $log_msg, FILE_APPEND);
        } 
    
        $log_entry = ob_get_clean();
        $log_timestamp = str_replace(':', '_', str_replace(' ', '_', $timestamp));

        log_data($log_entry, $log_timestamp);

        $response['msg'] = 'Success';
        $response['log'] = $log_entry;
        if ($report) {
            $response['data'] = $report_data;
        }
        echo json_encode($response);

    }
    catch (Exception $e) {
		$response['error'] = true;
		$response['msg'] = $e->getMessage();
		echo json_encode($response);
	}

// =====================================
// * DEBUG
// =====================================

    // echo '<pre>';
    // // echo json_encode($intercard_teller);
    // // echo json_encode($qubica_overview);
    // echo '</pre>';

    // echo '<pre>'; 
    //     echo '<h3>$all_data[game]</h3>';
    //     print_r($all_data['game']);
    // echo '</pre>';
    // echo '<pre>'; 
    //     echo '<h3>$qubica_overview</h3>';
    //     print_r($qubica_overview);
    // echo '</pre>';
    // echo '<pre>'; 
    //     echo '<h3>$qubica_receipts</h3>';
    //     print_r($qubica_receipts);
    // echo '</pre>';
    // echo '<pre>'; 
    //     echo '<h3>$qubica_receipt_items</h3>';
    //     print_r($qubica_receipt_items);
    // echo '</pre>';
    // echo '<pre>'; 
    //     echo '<h3>$intercard_teller</h3>';
    //     print_r($intercard_teller);
    // echo '</pre>';
    // echo '<pre>'; 
    //     echo '<h3>$intercard_pos</h3>';
    //     print_r($intercard_pos);
    // echo '</pre>';
    // echo '<pre>'; 
    //     echo '<h3>$intercard_game</h3>';
    //     print_r($intercard_game);
    // echo '</pre>';

    // echo "<style>
    //     pre {
    //         height: 300px;
    //         overflow: scroll;
    //         width: 50%;
    //     }
    // </style>";