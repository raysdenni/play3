<?php
error_reporting(0);
$bearer = 'Bearer XXXX';
function get($url, $bearer)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Authority: awsapi.play3.gg';
    $headers[] = 'Accept: application/json';
    $headers[] = 'Accept-Language: en-US,en;q=0.9,id;q=0.8';
    $headers[] = 'Access-Control-Allow-Origin: *';
    $headers[] = 'Authorization: ' . $bearer;
    $headers[] = 'Dnt: 1';
    $headers[] = 'Origin: https://app.play3.gg';
    $headers[] = 'Referer: https://app.play3.gg/';
    $headers[] = 'Sec-Ch-Ua: "Chromium";v="106", "Google Chrome";v="106", "Not;A=Brand";v="99"';
    $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
    $headers[] = 'Sec-Ch-Ua-Platform: "Windows"';
    $headers[] = 'Sec-Fetch-Dest: empty';
    $headers[] = 'Sec-Fetch-Mode: cors';
    $headers[] = 'Sec-Fetch-Site: same-site';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    return $result;
    curl_close($ch);
}

function post($url, $data, $bearer)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Authority: awsapi.play3.gg';
    $headers[] = 'Accept: application/json';
    $headers[] = 'Accept-Language: en-US,en;q=0.9,id;q=0.8';
    $headers[] = 'Access-Control-Allow-Origin: *';
    $headers[] = 'Authorization: ' . $bearer;
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Dnt: 1';
    $headers[] = 'Origin: https://app.play3.gg';
    $headers[] = 'Referer: https://app.play3.gg/';
    $headers[] = 'Sec-Ch-Ua: "Chromium";v="106", "Google Chrome";v="106", "Not;A=Brand";v="99"';
    $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
    $headers[] = 'Sec-Ch-Ua-Platform: "Windows"';
    $headers[] = 'Sec-Fetch-Dest: empty';
    $headers[] = 'Sec-Fetch-Mode: cors';
    $headers[] = 'Sec-Fetch-Site: same-site';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36';
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    return $result;
    curl_close($ch);
}


echo "Link Course: ";
$link_course = trim(fgets(STDIN));
$link_course = str_replace("https://app.play3.gg/section/", "", $link_course);

$get_course = get("https://awsapi.play3.gg/api/dashboard/learn-detail-v2/" . $link_course, $bearer);
$get_course = json_decode($get_course, true);

$learn_content = $get_course['learn']['learn_contents'];

// get quiz and video id
$vid_id = [];
$quiz_id = [];
foreach ($learn_content as $key => $content) {
    $vid_id[] = $content['id'];
    $quiz_id[] = $content['quiz']['id'];
}

// view video
foreach ($vid_id as $key => $vid) {
    view_vid:
    $view_vid = post("https://awsapi.play3.gg/api/dashboard/update-learn-video-progress", '{"learn_content_id":' . $vid . ',"last_watched_at":0,"is_video_finished":true}', $bearer);
    echo "Try to view Video " . $vid . PHP_EOL;
    if (preg_match("/Too Many Attempts./", $view_vid)) {
        echo "Failed View Video, Too Many Attempts. Please wait 1 minute" . PHP_EOL;
        sleep(60);
        goto view_vid;
    } else {
        echo "View Video " . $vid . " Success" . PHP_EOL;
        try {
            $get_quiz = get("https://awsapi.play3.gg/api/dashboard/learn-quiz/" . $quiz_id[$key], $bearer);
            $get_quiz = json_decode($get_quiz, true);
            $learn_content_id = $get_quiz['learn_content_id'];
            $temp_answer = $get_quiz['quiz']['quiz_question'][0]['quiz_answer'][0]['id'];
            send_answer:
            echo "Sending Temporary Answer: " . $temp_answer . PHP_EOL;
            $send_answer = post("https://awsapi.play3.gg/api/dashboard/submit-learn-content-quiz-answer", '{"learn_content_id":' . $learn_content_id . ',"is_timeout":false,"member_quiz_answers":[{"quiz_question_id":' . $quiz_id[$key] . ',"quiz_answer_id":' . $temp_answer . '}]}', $bearer);
            if (preg_match("/Too Many Attempts./", $send_answer)) {
                echo "Failed Sending Answer, Too Many Attempts. Please wait 1 minute" . PHP_EOL;
                sleep(60);
                goto send_answer;
            } else {
                echo "Send Temporary Answer Success" . PHP_EOL;
                echo "Checking Correct Answer..." . PHP_EOL;
                $correct_answer = json_decode($send_answer, true);
                $correct_answer = $correct_answer['quiz']['quiz_question'][0]['correct_answer_id'];
                echo "Correct Answer: " . $correct_answer . PHP_EOL;
                if ((int)$correct_answer == (int)$temp_answer) {
                    echo "Correct Answer" . PHP_EOL;
                    echo "==================================================================================". PHP_EOL . PHP_EOL;
                } else {
                    echo "Wrong Answer" . PHP_EOL;
                    send_correct_answer:
                    $send_answer = post("https://awsapi.play3.gg/api/dashboard/submit-learn-content-quiz-answer", '{"learn_content_id":' . $learn_content_id . ',"is_timeout":false,"member_quiz_answers":[{"quiz_question_id":' . $quiz_id[$key] . ',"quiz_answer_id":' . $correct_answer . '}]}', $bearer);
                    // print_r($send_answer);
                    if (preg_match("/Too Many Attempts./", $send_answer)) {
                        echo "Failed Sending Answer, Too Many Attempts. Please wait 1 minute" . PHP_EOL;
                        sleep(60);
                        goto send_correct_answer;
                    } else {
                        echo "Send Correct Answer Success" . PHP_EOL;
                        echo "==================================================================================". PHP_EOL . PHP_EOL;
                    }
                }
            }
        } catch (Exception $th) {
            continue;
        }
    }
}