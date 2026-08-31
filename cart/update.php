<?php
require_once '../config/db.php';

$isAjaxQuantity = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    && ($_POST['action'] ?? 'update') === 'update';

function respondCartUpdate(bool $isAjax, string $redirectUrl, array $payload = []): void
{
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . $redirectUrl);
    exit;
}

function cartUpdateFailure(bool $isAjax, string $redirectUrl, int $productId, string $message, int $quantity = 1): void
{
    if ($isAjax) {
        respondCartUpdate(true, $redirectUrl, [
            'success' => false,
            'product_id' => $productId,
            'quantity' => $quantity,
            'message' => $message,
        ]);
    }

    $_SESSION['error'] = $message;
    respondCartUpdate(false, $redirectUrl);
}

function getCartUpdateTotals(mysqli $conn, int $itemProductId): array
{
    $cart = $_SESSION['cart'] ?? [];
    $productIds = array_values(array_filter(array_map('intval', array_keys($cart)), static function (int $id): bool {
        return $id > 0;
    }));
    $totals = [
        'item_subtotal' => 0,
        'cart_subtotal' => 0,
        'voucher_discount' => 0,
        'shipping_fee' => 0,
        'final_total' => 0,
        'cart_count' => array_sum($cart),
    ];

    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT p.ProductID, p.Price AS OriginalPrice, ap.DiscountRate
                FROM product p
                LEFT JOIN (
                    SELECT pd.ProductID, MAX(pd.DiscountRate) AS DiscountRate
                    FROM promotion_detail pd
                    JOIN promotion pr ON pd.PromotionID = pr.PromotionID
                    WHERE NOW() BETWEEN COALESCE(pd.StartDate, pr.StartDate) AND COALESCE(pd.EndDate, pr.EndDate)
                    GROUP BY pd.ProductID
                ) ap ON p.ProductID = ap.ProductID
                WHERE p.ProductID IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $types = str_repeat('i', count($productIds));
            $stmt->bind_param($types, ...$productIds);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $discountRate = isset($row['DiscountRate']) ? floatval($row['DiscountRate']) : 0;
                    $unitPrice = floatval($row['OriginalPrice']) - (floatval($row['OriginalPrice']) * $discountRate / 100);
                    $subtotal = $unitPrice * intval($cart[$row['ProductID']] ?? 0);
                    $totals['cart_subtotal'] += $subtotal;
                    if ((int)$row['ProductID'] === $itemProductId) {
                        $totals['item_subtotal'] = $subtotal;
                    }
                }
            }
            $stmt->close();
        }
    }

    $appliedVoucher = $_SESSION['applied_voucher'] ?? null;
    if ($appliedVoucher && isset($_SESSION['user'])) {
        $totals['voucher_discount'] = floatval($appliedVoucher['value']);
    } else {
        unset($_SESSION['applied_voucher']);
    }
    $totals['final_total'] = max(0, $totals['cart_subtotal'] + $totals['shipping_fee'] - $totals['voucher_discount']);
    $totals['cart_count'] = array_sum($_SESSION['cart'] ?? []);
    return $totals;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('cart/cart.php'));
    exit;
}

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : 'update';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if ($productId <= 0) {
    cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Sản phẩm không hợp lệ.');
}

if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$productId])) {
    cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Sản phẩm không còn trong giỏ hàng.');
}

$customerId = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : null;

if ($action === 'delete') {
    if ($customerId !== null && $customerId > 0) {
        // Xóa trong database
        $stmtCart = $conn->prepare("SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active' LIMIT 1");
        $stmtCart->bind_param("i", $customerId);
        $stmtCart->execute();
        $resCart = $stmtCart->get_result();
        if ($resCart->num_rows > 0) {
            $rowCart = $resCart->fetch_assoc();
            $cartId = intval($rowCart['CartID']);
            $stmtDel = $conn->prepare("DELETE FROM cart_detail WHERE CartID = ? AND ProductID = ?");
            $stmtDel->bind_param("ii", $cartId, $productId);
            $stmtDel->execute();
            $stmtDel->close();
        }
        $stmtCart->close();
    }
    unset($_SESSION['cart'][$productId]);
    unset($_SESSION['applied_voucher']);
    $_SESSION['success'] = 'Đã xóa sản phẩm khỏi giỏ hàng.';
} else {
    // Trường hợp cập nhật số lượng
    if ($quantity <= 0) {
        if ($isAjaxQuantity) {
            cartUpdateFailure(true, url('cart/cart.php'), $productId, 'Số lượng phải lớn hơn hoặc bằng 1.', intval($_SESSION['cart'][$productId]));
        }
        if ($customerId !== null && $customerId > 0) {
            // Xóa trong database
            $stmtCart = $conn->prepare("SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active' LIMIT 1");
            $stmtCart->bind_param("i", $customerId);
            $stmtCart->execute();
            $resCart = $stmtCart->get_result();
            if ($resCart->num_rows > 0) {
                $rowCart = $resCart->fetch_assoc();
                $cartId = intval($rowCart['CartID']);
                $stmtDel = $conn->prepare("DELETE FROM cart_detail WHERE CartID = ? AND ProductID = ?");
                $stmtDel->bind_param("ii", $cartId, $productId);
                $stmtDel->execute();
                $stmtDel->close();
            }
            $stmtCart->close();
        }
        unset($_SESSION['cart'][$productId]);
        unset($_SESSION['applied_voucher']);
        $_SESSION['success'] = 'Đã xóa sản phẩm khỏi giỏ hàng.';
    } else {
        // Kiểm tra tồn kho của sản phẩm
        $stmt = $conn->prepare("SELECT ProductName, Quantity, Status FROM product WHERE ProductID = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $product = $res->fetch_assoc();
            if ($isAjaxQuantity && ((int)$product['Quantity'] <= 0 || $product['Status'] === 'Hết hàng')) {
                $stmt->close();
                cartUpdateFailure(true, url('cart/cart.php'), $productId, 'Sản phẩm đã hết hàng.', intval($_SESSION['cart'][$productId]));
            }
            $finalQty = $quantity;
            $warningMessage = null;
            if ($quantity > $product['Quantity']) {
                $finalQty = $product['Quantity'];
                $warningMessage = 'Số lượng đã được điều chỉnh theo tồn kho hiện tại: ' . $product['Quantity'] . ' sản phẩm.';
                if (!$isAjaxQuantity) {
                    $_SESSION['warning'] = 'Số lượng cập nhật vượt quá tồn kho. Tự động điều chỉnh về tối đa: ' . $product['Quantity'] . ' sản phẩm.';
                }
            } elseif (!$isAjaxQuantity) {
                $_SESSION['success'] = 'Đã cập nhật số lượng giỏ hàng thành công.';
            }
            
            if ($customerId !== null && $customerId > 0) {
                // Cập nhật trong database
                $stmtCart = $conn->prepare("SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active' LIMIT 1");
                if (!$stmtCart) {
                    $stmt->close();
                    cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                }
                $stmtCart->bind_param("i", $customerId);
                if (!$stmtCart->execute()) {
                    $stmt->close();
                    cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                }
                $resCart = $stmtCart->get_result();
                if ($resCart->num_rows > 0) {
                    $rowCart = $resCart->fetch_assoc();
                    $cartId = intval($rowCart['CartID']);
                    
                    // Đảm bảo có dòng ghi trong cart_detail để UPDATE
                    $stmtCheck = $conn->prepare("SELECT 1 FROM cart_detail WHERE CartID = ? AND ProductID = ?");
                    if (!$stmtCheck) {
                        $stmtCart->close();
                        $stmt->close();
                        cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                    }
                    $stmtCheck->bind_param("ii", $cartId, $productId);
                    if (!$stmtCheck->execute()) {
                        $stmtCheck->close();
                        $stmtCart->close();
                        $stmt->close();
                        cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                    }
                    $resCheck = $stmtCheck->get_result();
                    $stmtCheck->close();
                    
                    if ($resCheck->num_rows > 0) {
                        $stmtUp = $conn->prepare("UPDATE cart_detail SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
                        if (!$stmtUp) {
                            $stmtCart->close();
                            $stmt->close();
                            cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                        }
                        $stmtUp->bind_param("iii", $finalQty, $cartId, $productId);
                        if (!$stmtUp->execute()) {
                            $stmtUp->close();
                            $stmtCart->close();
                            $stmt->close();
                            cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                        }
                        $stmtUp->close();
                    } else {
                        $stmtIn = $conn->prepare("INSERT INTO cart_detail (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
                        if (!$stmtIn) {
                            $stmtCart->close();
                            $stmt->close();
                            cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                        }
                        $stmtIn->bind_param("iii", $cartId, $productId, $finalQty);
                        if (!$stmtIn->execute()) {
                            $stmtIn->close();
                            $stmtCart->close();
                            $stmt->close();
                            cartUpdateFailure($isAjaxQuantity, url('cart/cart.php'), $productId, 'Không thể cập nhật giỏ hàng lúc này.', intval($_SESSION['cart'][$productId]));
                        }
                        $stmtIn->close();
                    }
                } elseif ($isAjaxQuantity) {
                    $stmtCart->close();
                    $stmt->close();
                    cartUpdateFailure(true, url('cart/cart.php'), $productId, 'Không tìm thấy giỏ hàng đang hoạt động.', intval($_SESSION['cart'][$productId]));
                }
                $stmtCart->close();
            }
            
            $_SESSION['cart'][$productId] = $finalQty;
        } elseif ($isAjaxQuantity) {
            $stmt->close();
            cartUpdateFailure(true, url('cart/cart.php'), $productId, 'Sản phẩm không còn tồn tại.', intval($_SESSION['cart'][$productId]));
        }
        $stmt->close();

        if ($isAjaxQuantity) {
            $totals = getCartUpdateTotals($conn, $productId);
            $response = array_merge([
                'success' => true,
                'product_id' => $productId,
                'quantity' => $finalQty,
            ], $totals);
            if ($warningMessage !== null) {
                $response['warning'] = $warningMessage;
            }
            respondCartUpdate(true, url('cart/cart.php'), $response);
        }
    }
}

respondCartUpdate(false, url('cart/cart.php'));
?>
