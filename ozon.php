<?php

Class searchMessage
{


    public function database()
    {
        global $link;
        $link = mysqli_connect('****', '*****', '*****', '*****');

        if ($link == false) {
            print("Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error());
        } else {
            print("Соединение установлено успешно") . '<br>';
        }

    }

    public function post($host, $data)
    {
        $api_key = '***************';
        $client_id = *******;


        $headers = [
            'Client-Id: ' . $client_id,
            'Api-Key:' . $api_key,
            'Content-Type: application/json'
        ];
        $curl = curl_init($host);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $return = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            $res = "Curl Error #:" . $err;
        } else {
            $res = json_decode($return, true);

        }
        return $res;

    }




    public function addMessage($start = 1, $end = 7)
    {
//
        $this->database();
        global $link;
        //$page = 1;

///
        $pages = range($start, $end);
        foreach ($pages as $p) {
            $data = [
                //'chat_id_list'=> [ ],
                'page' => $p,
                'page_size' => 100,
                'with' => [

                    'first_unread_message_id' => true,
                    'unread_count' => true

                ]

            ];

            @$page = $this->post('https://api-seller.ozon.ru/v1/chat/list', $data);


            foreach (@$page as $element) {
                if (empty($element)) {
                    return false;
                }
                if (is_array($element)) {


                    foreach ($element as $key => $item) {

                        //echo $key ."<br>";
////        print_r($item['id']);//
                        $datas = [
                            'chat_id' => $item['id'],
                            'from_message_id' => 0,
                            'limit' => 1000
                        ];
                        $message = $this->post('https://api-seller.ozon.ru/v1/chat/updates', $datas);

                        //print_r(post('https://api-seller.ozon.ru/v1/chat/updates', $datas));
                        foreach ($message as $i) {

                            foreach ($i as $a) {

                                $sample_number = " ";
                                foreach ($a['user'] as $b) {
                                    $sample_number .= $b;
                                }
                                $seller_customer = preg_replace('/\d/', '', $sample_number);
                                //echo $seller_customer . "<br>";
                                (int)$id = preg_replace('/[a-zA-Zа-яА-Я]/', '', $sample_number);
                                //echo $id ."<br>";
                                //echo $a['id'] . "<br>";
                                $text = $a['text'];
                                $text = str_replace('"', '', $text);
                                $test = stristr($text, 'Запрос на скидку');
                                if ($test) {
                                    (int)$chat_id = $a['id'];
                                    $sql = "SELECT chat_id FROM SalesTable WHERE chat_id = '$chat_id'";
                                    $result = mysqli_query($link, $sql);
                                    $row = $result->fetch_assoc();
                                    if ($row == 0) {
                                        $sql = "INSERT INTO SalesTable (chat_id, client_id, text, created_at) VALUES ('" . $chat_id . "' , '" . $id . "' , '" . $test . "', '" . $a['created_at'] . "')";
                                        $result = mysqli_query($link, $sql);
                                        if ($result == false) {
                                            echo "Ошибка: " . $sql . "<br>" . mysqli_error($link);
                                        }
                                    }
//                                    else {
//                                        echo "чат с таким id уже существует";
//                                    }
/////////////////////////////Вывод в браузер для проверки//////////////////////////////////////////////////
//                                echo $a['id'] . "<br>";
//                                echo $seller_customer . "<br>";
//                                echo $id . "<br>";
//                                //echo $test;
//                                echo $text;
//                                echo $a['created_at'] . "<br>";
                                    //}
//  //                            echo $a['created_at']. "<br>";


                                    ////echo $a['text'] . "<br>";
                                }

                            }
                        }
                    }
                }
            }
            /////////// пауза между итерацией циклов

            sleep(1);
        }

        /////окончание функции
    }
}
/////Вызов метода класса
//$search = new searchMessage();
//$search->addMessage(1,8);
///////////////

class stop extends searchMessage
{

    public function post($host, $data)
    {
    return parent::post($host, $data); // TODO: Change the autogenerated stub
    }

    public function stopFunction()
    {

        $this->database();

        global $link;
        $page = 1;
        $data = [//        'chat_id_list'=> [ ],
            'page' => $page,
            'page_size' => 1000,
            'with' => ['first_unread_message_id' => true,
                'unread_count' => true]];

        @$page = $this->post('https://api-seller.ozon.ru/v1/chat/list', $data);
        //print_r(post('https://api-seller.ozon.ru/v1/chat/list', $data))
/////////////////получить общее количество непрочитанных сообщений которая сохраняется в бд////////////
        foreach (@$page as $element) {
            $stopnumber = (int)$element;
//        if (is_array($element)) {
////            foreach ($element as $key => $item) {
////            }
//
//        }
        }

        echo $stopnumber;
////////////////////////////////////////////////////////////////////////////////////////////
/// получение сохраненного числа из таблицы////////////////////////////////////////////////
        $sql = "SELECT number FROM stoptable ";
        $result = mysqli_query($link, $sql);
        $row = $result->fetch_assoc();
        $stopvalue = 0;
        foreach ($result as $rows) {
            $stopvalue += $rows['number'];

        }
        echo $stopvalue . "<br>";
/////////////////////проверки и запуск функции добавления сообщений в случае если строка пустая или меньше общего количества несохранённых сообщений////////////////////
        if ($stopvalue == $stopnumber) {
            echo " Новых сообшений не появилось" . "<br>";
        }elseif ($row == 0) {

            $this->addMessage();

            $sql = "INSERT INTO stoptable (number) VALUES ('" . $stopnumber . "')";
            $result = mysqli_query($link, $sql);
            if ($result == false) {
                echo "Ошибка: " . $sql . "<br>" . mysqli_error($link);
            }

        } elseif ($stopnumber > $stopvalue) {

           $this->addMessage();

            $sql = "Update stoptable set number='" . $stopnumber . "' ";
            $result = mysqli_query($link, $sql);
            if ($result == false) {
                echo "Ошибка: " . $sql . "<br>" . mysqli_error($link);
            }
//
       }
//
    }
}

$test = new stop();
$test->stopFunction();
