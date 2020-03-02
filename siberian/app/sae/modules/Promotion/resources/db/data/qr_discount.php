<?php
$name = "QR Coupons";
$category = "monetization";

# Install icons
$icons = array(
    "/discount/discount1.png",
    "/discount/discount2.png",
    "/discount/discount3.png",
    "/discount/discount4.png",
    "/discount/discount5.png",
);

$result = Siberian_Feature::installIcons($name, $icons);

# Install the Feature
$data = array(
    'library_id'                    => $result["library_id"],
    'icon_id'                       => $result["icon_id"],
    "code"                          => "qr_discount",
    "name"                          => $name,
    "model"                         => "Promotion_Model_Promotion",
    "desktop_uri"                   => "promotion/application/",
    "mobile_uri"                    => "promotion/mobile_list/",
    "mobile_view_uri"               => "promotion/mobile_view/",
    "mobile_view_uri_parameter"     => "promotion_id",
    "social_sharing_is_available"   => 1,
    "position"                      => 25,
    "use_my_account"                => 1
);

$option = Siberian_Feature::install($category, $data, array('code'));

# Layouts
$remove_layout_data = array(3, 4);
$layout_data = array(1);
$slug = "promotion";

Siberian_Feature::installLayouts($option->getId(), $slug, $layout_data);
Siberian_Feature::removeLayouts($option->getId(), $slug, $remove_layout_data);

# Icons Flat
$icons = array(
    "/discount/discount1-flat.png",
    "/discount/discount2-flat.png",
    "/discount/discount3-flat.png",
);

Siberian_Feature::installIcons("{$name}-flat", $icons);