function curl_call($times = 1) {
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_TIMEOUT, 5);
   curl_setopt($ch, CURLOPT_URL, 'http://demon.at');
   $curl_version = curl_version();print_r($curl_version);
   if ($curl_version['version_number'] >= 462850) {//因为php的CURLOPT_CONNECTTIMEOUT_MS需要 curl_version 7.16.2,这个值就是这个版本的数字版本号.还需要注意的是, php版本要大于5.2.3
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 20); //连接超时的时间, 单位:ms
      curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
   } else {
      //throw new Exception('this curl version is too low, version_num : ' . $curl_version['version']);
   }
   $res = curl_exec($ch);
   curl_close($ch);
   if (false === $res) {
      if (curl_errno($ch) == CURLE_OPERATION_TIMEOUTED
             and $times != 5 ) {//最大重试阀值
         $times += 1;
         return curl_call($times);
      }
   }

   return $res;
}
