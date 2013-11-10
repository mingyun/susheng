
//json_encode 对中文的处理是转成了对应的 unicode 码的十六进制表示符 \u4f60，（和 js 的 escape 函数类似（%u4f60）） ，即 0x4f60。因此，我们只需要将 unicode 码（UCS-2）转成 utf-8 编码的汉字即可
/**
 * json_encode 支持中文版
 * @param mixed $data 参数和 json_encode 完全相同
 */
function json_encode_cn($data) {
	$data = json_encode($data);
	return preg_replace("/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2', 'UTF-8', pack('H*', '$1'));", $data);
}
