<?php

header("Content-Type: text/html; charset=utf-8");

$options = (object)get_option('woocommerce_wc_cloudpayments_gateway_settings');

if (isset($_GET['action']) && $_GET['action'] == 'add_payment_method') {
    
    $widget_f = 'auth';
    
    $data = '{';
    $data .= 'publicId: "' . $options->public_id . '",';
    $data .= 'description: "Добавление карты",';
    $data .= 'amount:' . 1 . ',';
    $data .= 'currency:"' . $options->currency . '",';
    $data .= 'skin:"' . $options->skin . '",';
    $data .= 'accountId: "' . get_current_user_id() . '",';
    $data .= 'data:{ add_payment_method: "1" }';
    $data .= '},';
    
} else {
    
    global $woocommerce;
    $order_id      = $_GET['order_id'];
    $order         = new WC_Order($order_id);
    $title         = array();
    $items_array   = array();
    $items         = $order->get_items();
    $shipping_data = array(
        "label"    => "Доставка",
        "price"    => number_format((float)$order->get_total_shipping() + abs((float)$order->get_shipping_tax()), 2, '.', ''),
        "quantity" => "1.00",
        "amount"   => number_format((float)$order->get_total_shipping() + abs((float)$order->get_shipping_tax()), 2, '.', ''),
        "vat"      => ($options->delivery_taxtype == "null") ? null : $options->delivery_taxtype,
        'method'   => (int)$options->kassa_method,
        'object'   => 4,
        "ean"      => null
    );
    
    foreach ($items as $item) {
        if ($options->kassa_enabled == 'yes') {
            $product       = $order->get_product_from_item($item);
            $items_array[] = array(
                "label"    => $item['name'],
                "price"    => number_format((float)$product->get_price(), 2, '.', ''),
                "quantity" => number_format((float)$item['quantity'], 2, '.', ''),
                "amount"   => number_format((float)$item['total'] + abs((float)$item['total_tax']), 2, '.', ''),
                "vat"      => ($options->kassa_taxtype == "null") ? null : $options->kassa_taxtype,
                'method'   => (int)$options->kassa_method,
                'object'   => (int)$options->kassa_object,
                "ean"      => ($options->kassa_skubarcode == 'yes') ? ((strlen($product->get_sku()) < 1) ? null : $product->get_sku()) : null
            );
        }
        $title[] = $item['name'] . (isset($item['pa_ver']) ? ' ' . $item['pa_ver'] : '');
    }
    
    if ($options->kassa_enabled == 'yes' && $order->get_total_shipping() > 0) {
        $items_array[] = $shipping_data;
    }
    
    $kassa_array = array(
        "cloudPayments" => (array(
            "customerReceipt" => array(
                "Items"            => $items_array,
                "taxationSystem"   => $options->kassa_taxsystem,
                'calculationPlace' => $_SERVER['SERVER_NAME'],
                "email"            => $order->get_billing_email(),
                "phone"            => $order->get_billing_phone(),
            )
        ))
    );
    
    $title    = implode(', ', $title);
    $widget_f = 'charge';
    
    if ($options->enabledDMS != 'no') {
        $widget_f = 'auth';
    }
    
    $accountId = '';
    
    if (is_user_logged_in()) {
        $accountId = $_GET['cp_save_card'] ? 'accountId:' . $order->get_user_id() . ',' : '';
    }
    
    $data = '{';
    $data .= 'publicId: "' . $options->public_id . '",';
    $data .= 'description: "' . $options->order_text . ' ' . $order_id . '",';
    $data .= 'amount:' . $order->get_total() . ',';
    $data .= 'currency: "' . $options->currency . '",';
    $data .= 'skin: "' . $options->skin . '",';
    $data .= 'invoiceId:' . $order_id . ',';
    $data .= $accountId;
    $data .= 'email: "' . $order->get_billing_email() . '",';
    $data .= 'data:' . (($options->kassa_enabled == 'yes') ? json_encode($kassa_array) : "{}");
    $data .= '},';
}

?>
<title>CloudPayments</title>
<script src="https://widget.cloudpayments.ru/bundles/cloudpayments?cms=Wordpress"></script>
<script>
    window.onload = function () {
        var widget = new cp.CloudPayments({language: '<?php echo $options->language?>'});
        widget.<?php echo $widget_f ?>(
            <?php echo $data; ?>
            function (options) {
                window.location.replace('<?php echo $_GET['return_ok'] ?>');
            },
            function (reason, options) {
                window.location.replace('<?php echo $_GET['return_ok'] ?>');
            }
        );
    }
</script>

