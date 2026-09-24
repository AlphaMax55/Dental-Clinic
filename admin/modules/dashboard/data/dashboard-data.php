<?php
// ===== NAKİT AKIŞI =====
function getCashflowData($db) {
    return [
        'total_cash' => 130232,
        'cash_change' => 4.51,
        'total_income' => 1412,
        'income_change' => 4.51,
        'total_expenses' => 612.34,
        'expenses_change' => 2.41,
        'monthly_data' => [
            'Jan' => 10897, 'Feb' => 12450, 'Mar' => 11230,
            'Apr' => 13450, 'May' => 12100, 'Jun' => 14230,
            'Jul' => 13890, 'Aug' => 15120, 'Sep' => 14780,
            'Oct' => 15890, 'Nov' => 16230, 'Dec' => 17120
        ]
    ];
}

// ===== HASTA İSTATİSTİKLERİ =====
function getPatientStats($db) {
    $total_patients = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'hasta'")->fetchColumn() ?: 142;
    $new_patients = $db->query("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'hasta' AND MONTH(created_at) = MONTH(NOW())")->fetchColumn() ?: 21;
    
    return [
        'total' => $total_patients,
        'new_this_month' => $new_patients,
        'returning' => $total_patients - $new_patients,
        'new_percent' => round(($new_patients / $total_patients) * 100, 2),
        'returning_percent' => round((($total_patients - $new_patients) / $total_patients) * 100, 2)
    ];
}

// ===== POPÜLER TEDAVİLER =====
function getPopularTreatments($db) {
    return [
        ['name' => 'Scaling Teeth', 'count' => 342],
        ['name' => 'Tooth Extraction', 'count' => 287],
        ['name' => 'General Checkup', 'count' => 256],
        ['name' => 'Root Canal', 'count' => 189],
        ['name' => 'Dental Implant', 'count' => 156]
    ];
}

// ===== GİDER DAĞILIMI =====
function getExpenseData() {
    return [
        ['category' => 'Rental Cost', 'percentage' => 30, 'amount' => 26000],
        ['category' => 'Wages', 'percentage' => 22, 'amount' => 16500],
        ['category' => 'Medical Equipment', 'percentage' => 20, 'amount' => 15640],
        ['category' => 'Supplies', 'percentage' => 18, 'amount' => 13564],
        ['category' => 'Promotion Costs', 'percentage' => 8, 'amount' => 7800],
        ['category' => 'Other', 'percentage' => 2, 'amount' => 4200]
    ];
}

// ===== STOK DURUMU =====
function getStockData() {
    return [
        'total_asset' => 53000,
        'total_product' => 442,
        'available' => 356,
        'low_stock' => 68,
        'out_of_stock' => 18,
        'low_stock_items' => [
            ['name' => 'Dental Brush', 'qty' => 3],
            ['name' => 'Charmflex Regular', 'qty' => 2],
            ['name' => 'Anesthetic', 'qty' => 4],
            ['name' => 'Gloves M', 'qty' => 5]
        ]
    ];
}

// ===== BEKLEYEN İŞLEMLER =====
function getPendingAlerts() {
    return [
        'pending_reviews' => 12,
        'pending_messages' => 8,
        'pending_appointments' => 15,
        'pending_payments' => 5
    ];
}

// ===== SON AKTİVİTELER =====
function getRecentActivities() {
    return [
        ['user' => 'John Smith', 'action' => 'New appointment booked', 'time' => '5 min ago'],
        ['user' => 'Sarah Johnson', 'action' => 'Treatment completed', 'time' => '15 min ago'],
        ['user' => 'Mike Wilson', 'action' => 'Payment received', 'time' => '25 min ago'],
        ['user' => 'Emma Davis', 'action' => 'New patient registered', 'time' => '1 hour ago'],
        ['user' => 'Robert Brown', 'action' => 'Prescription refilled', 'time' => '2 hours ago']
    ];
}

// ===== HASTA ÖZETİ =====
function getPatientSummary() {
    return [
        'name' => 'Maciej Zakoscielny',
        'age' => 39,
        'gender' => 'M',
        'dob' => '03/06/1956',
        'phone' => '+49 7235 39 595',
        'email' => 'maciejz@gmail.com',
        'alergies' => ['Billing Alert'],
        'pcp' => 'Elijah Manning',
        'language' => 'English',
        'translation' => 'No',
        'referral' => 'Barrett Moving',
        'high_priority' => 'Cancelled',
        'problem_list' => [
            ['code' => '10 E23.3', 'name' => 'Diabetes'],
            ['code' => '9 498.2', 'name' => 'COPD'],
            ['code' => '10 j33.2', 'name' => 'Hypertension'],
            ['code' => '10 i19', 'name' => 'Hyperthermia']
        ]
    ];
}
?>