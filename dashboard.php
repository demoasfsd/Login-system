<?php
// dashboard.php
// Simple Sales Dashboard (Chart.js + Bootstrap) using mysqli (no PDO)
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
require __DIR__ . '/config_mysqli.php';
require __DIR__ . '/csrf.php';

// *** FIX 1: ตรวจสอบสิทธิ์การเข้าถึง ***
// ต้องตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่ ถ้ายังให้ redirect ไปหน้า login
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// *** END FIX 1 ***

// *** FIX 2: นิยามฟังก์ชัน fetch_all ***
// ฟังก์ชันสำหรับเรียกข้อมูลทั้งหมดจาก Query
if (!function_exists('fetch_all')) {
    function fetch_all($mysqli, $sql): array {
        $res = $mysqli->query($sql);
        if (!$res) {
            // ในทางปฏิบัติควรมีการ log error ตรงนี้
            error_log("SQL Error: " . $mysqli->error . " for query: " . $sql);
            return [];
        }
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();
        return $rows;
    }
}
// *** END FIX 2 ***

// เตรียมข้อมูลสำหรับกราฟต่าง ๆ
$monthly = fetch_all($mysqli, "SELECT ym, net_sales FROM v_monthly_sales");
$category = fetch_all($mysqli, "SELECT category, net_sales FROM v_sales_by_category");
$region = fetch_all($mysqli, "SELECT region, net_sales FROM v_sales_by_region");
// *** FIX 3: Top Products - สั่งเรียงตาม net_sales DESC และ limit 10 เพื่อให้เป็น Top 10 ที่ถูกต้อง ***
$topProducts = fetch_all($mysqli, "SELECT product_name, qty_sold, net_sales FROM v_top_products ORDER BY net_sales DESC LIMIT 10");
$payment = fetch_all($mysqli, "SELECT payment_method, net_sales FROM v_payment_share");
// *** FIX 4: Hourly - ตรวจสอบว่า field hour_of_day ถูกดึงมาเป็น string/int ที่ถูกต้องตามที่ JS คาดหวังหรือไม่ ***
$hourly = fetch_all($mysqli, "SELECT LPAD(hour_of_day, 2, '0') as hour_of_day, net_sales FROM v_hourly_sales ORDER BY hour_of_day");
$newReturning = fetch_all($mysqli, "SELECT date_key, new_customer_sales, returning_sales FROM v_new_vs_returning ORDER BY date_key");
$kpis = fetch_all($mysqli, "
  SELECT
    (SELECT SUM(net_amount) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS sales_30d,
    (SELECT SUM(quantity) 	FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS qty_30d,
    (SELECT COUNT(DISTINCT customer_id) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS buyers_30d
");
$kpi = $kpis ? $kpis[0] : ['sales_30d'=>0,'qty_30d'=>0,'buyers_30d'=>0];

// Helper for number format
function nf($n) { return number_format((float)$n, 2); }
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Retail DW Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <!-- ต้องเพิ่ม ChartDataLabels Library สำหรับแสดงป้ายข้อมูลบนแท่งกราฟ -->
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
  <style>
    /* Midnight Blue Theme */
    body {
      background: #0a192f; /* Dark Midnight Blue */
      color: #e2e8f0; /* slate-200 */
    }
    .card {
      background: #1e293b; /* slate-800 */
      border: 1px solid #334155; /* slate-700 */
      border-radius: 1rem;
      height: 100%; /* Make cards in a row the same height */
    }
    .card h5 {
      color: #e5e7eb; /* slate-100 */
    }
    .kpi {
      font-size: 1.4rem;
      font-weight: 700;
      color: #4ade80; /* bright-green-400 - Make KPI stand out */
    }
    .sub {
      color: #93c5fd; /* blue-300 */
      font-size: .9rem;
    }
    canvas {
      max-height: 360px;
    }

    /* Logout Button Style */
    .btn-logout {
      background-color: #334155; /* slate-700 */
      color: #e2e8f0; /* slate-200 */
      border: 1px solid #475569; /* slate-600 */
      font-weight: 500;
    }
    .btn-logout:hover {
      background-color: #475569; /* slate-600 */
      color: #fff;
    }
  </style>
</head>
<body class="p-3 p-md-4">
  <div class="container-fluid">
    
    <!-- Header with Title and Logout Button -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
      <div>
        <h2 class="mb-0">ยอดขาย (Retail DW) — Dashboard</h2>
        <span class="sub">แหล่งข้อมูล: MySQL (mysqli)</span>
      </div>
      <a href="logout.php" class="btn btn-logout mt-2 mt-md-0">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: -0.125em; margin-right: 0.25rem;">
          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2.146a.5.5 0 0 1-1 0V4.5H2v7h7.5V10a.5.5 0 0 1 1 0z"/>
          <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
        </svg>
        ออกจากระบบ
      </a>
    </div>

    <!-- KPI Cards - Using Bootstrap Grid -->
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="card p-3">
          <h5>ยอดขาย 30 วัน</h5>
          <div class="kpi">฿<?= nf($kpi['sales_30d']) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <h5>จำนวนชิ้นขาย 30 วัน</h5>
          <div class="kpi"><?= number_format((int)$kpi['qty_30d']) ?> ชิ้น</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <h5>จำนวนผู้ซื้อ 30 วัน</h5>
          <div class="kpi"><?= number_format((int)$kpi['buyers_30d']) ?> คน</div>
        </div>
      </div>
    </div>

    <!-- Charts grid - Using Bootstrap Grid -->
    <div class="row g-3">

      <div class="col-lg-8">
        <div class="card p-3">
          <h5 class="mb-2">ยอดขายรายเดือน (2 ปี)</h5>
          <canvas id="chartMonthly"></canvas>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card p-3">
          <h5 class="mb-2">สัดส่วนยอดขายตามหมวด</h5>
          <canvas id="chartCategory"></canvas>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card p-3">
          <h5 class="mb-2">Top 10 สินค้าขายดี</h5>
          <canvas id="chartTopProducts"></canvas>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card p-3">
          <h5 class="mb-2">ยอดขายตามภูมิภาค</h5>
          <canvas id="chartRegion"></canvas>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card p-3">
          <h5 class="mb-2">วิธีการชำระเงิน</h5>
          <canvas id="chartPayment"></canvas>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card p-3">
          <h5 class="mb-2">ยอดขายรายชั่วโมง</h5>
          <canvas id="chartHourly"></canvas>
        </div>
      </div>

      <div class="col-12">
        <div class="card p-3">
          <h5 class="mb-2">ลูกค้าใหม่ vs ลูกค้าเดิม (รายวัน)</h5>
          <canvas id="chartNewReturning"></canvas>
        </div>
      </div>

    </div>
  </div>

<script>
// เตรียมข้อมูลจาก PHP -> JS
// PHP: json_encode($data) จะส่ง [] ถ้าไม่มีข้อมูล ซึ่งเป็น Array ว่างที่ JS สามารถจัดการได้
const monthly = <?= json_encode($monthly, JSON_UNESCAPED_UNICODE) ?>;
const category = <?= json_encode($category, JSON_UNESCAPED_UNICODE) ?>;
const region = <?= json_encode($region, JSON_UNESCAPED_UNICODE) ?>;
const topProducts = <?= json_encode($topProducts, JSON_UNESCAPED_UNICODE) ?>;
const payment = <?= json_encode($payment, JSON_UNESCAPED_UNICODE) ?>;
const hourly = <?= json_encode($hourly, JSON_UNESCAPED_UNICODE) ?>;
const newReturning = <?= json_encode($newReturning, JSON_UNESCAPED_UNICODE) ?>;

// Utility: pick labels & values
// เพิ่มการตรวจสอบว่าค่าที่ได้เป็นตัวเลขหรือไม่ ก่อนใช้ parseFloat() เพื่อความปลอดภัย
const toXY = (arr, x, y) => ({ 
    labels: arr.map(o => o[x]), 
    values: arr.map(o => o[y] !== null && o[y] !== undefined ? parseFloat(o[y]) : 0) 
});

// --- Global Chart.js Theme Config ---
const chartColors = ['#60a5fa', '#818cf8', '#a78bfa', '#f472b6', '#4ade80', '#fbbf24', '#22d3ee']; // blue, indigo, purple, pink, green, amber, cyan
const gridColor = 'rgba(255, 255, 255, 0.08)';
const tickColor = '#c7d2fe'; // indigo-200
const textColor = '#e5e7eb'; // slate-100

Chart.defaults.color = textColor; // Global text color
Chart.defaults.borderColor = gridColor; // Global border/grid color

// Options สำหรับการแสดง Data Label บนแท่งกราฟ (สำหรับ Bar/Hourly/Region)
const dataLabelPluginOptions = {
    datalabels: {
        align: 'end',
        anchor: 'end',
        offset: 8,
        color: tickColor,
        font: { size: 10, weight: 'bold' },
        formatter: (value) => {
            // แสดงเป็น K (พัน) หรือ M (ล้าน) เพื่อให้อ่านง่าย
            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
            return value.toLocaleString('th-TH');
        }
    }
};

// Global options for cartesian charts (line, bar)
const cartesianOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { labels: { color: textColor } }
  },
  scales: {
    x: {
      ticks: { color: tickColor },
      grid: { color: gridColor }
    },
    y: {
      ticks: { color: tickColor, callback: (value) => value.toLocaleString('th-TH') }, // Format Y-axis numbers (แสดงหลักพัน)
      grid: { color: gridColor },
      beginAtZero: true
    }
  }
};

// Global options for polar/pie charts (doughnut, pie)
const polarOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { color: textColor }
    }
  }
};
// --- End Global Theme ---


// Monthly
(() => {
  const ctx = document.getElementById('chartMonthly');
  if (!monthly.length) { ctx.parentElement.classList.add('d-none'); return; } // ซ่อนถ้าข้อมูลว่าง
  const {labels, values} = toXY(monthly, 'ym', 'net_sales');
  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ 
      label: 'ยอดขาย (฿)', 
      data: values, 
      tension: .25, 
      fill: true,
      backgroundColor: 'rgba(96, 165, 250, 0.2)', // Faded color 1
      borderColor: chartColors[0] // Solid color 1
    }] },
    options: cartesianOptions
  });
})();

// Category
(() => {
  const ctx = document.getElementById('chartCategory');
  if (!category.length) { ctx.parentElement.classList.add('d-none'); return; } // ซ่อนถ้าข้อมูลว่าง
  const {labels, values} = toXY(category, 'category', 'net_sales');
  new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values, backgroundColor: chartColors }] }, // Use color palette
    options: polarOptions
  });
})();

// Top products (เปลี่ยนเป็นกราฟแท่งแนวตั้ง)
(() => {
  const ctx = document.getElementById('chartTopProducts');
  if (!topProducts.length) { ctx.parentElement.classList.add('d-none'); return; } // ซ่อนถ้าข้อมูลว่าง
  
  // *** แก้ไข: เรียงลำดับจากน้อยไปมาก (ตามยอดขาย) เพื่อให้แท่งกราฟสูงสุดอยู่ขวา/บนสุดในแนวตั้ง ***
  const sortedProducts = [...topProducts].sort((a, b) => parseFloat(a.net_sales) - parseFloat(b.net_sales));
  
  const labels = sortedProducts.map(o => o.product_name);
  const sales = sortedProducts.map(o => o.net_sales !== null && o.net_sales !== undefined ? parseFloat(o.net_sales) : 0); 

  new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets: [{ 
      label: 'ยอดขาย (฿)', 
      data: sales, 
      backgroundColor: chartColors[1] // Color 2
    }] },
    options: {
      ...cartesianOptions,
      // *** ลบ indexAxis: 'y' ออก เพื่อให้เป็นกราฟแนวตั้ง (Vertical Bar) ***
      plugins: {
        ...cartesianOptions.plugins,
        ...dataLabelPluginOptions, // เพิ่ม Data Label
      },
      scales: {
        x: {
          ...cartesianOptions.scales.x,
          // *** ตั้งค่าให้ Labels เอียง เพื่อไม่ให้ชื่อสินค้าซ้อนกัน (เฉพาะกรณี Labels ยาว) ***
          ticks: {
              ...cartesianOptions.scales.x.ticks,
              maxRotation: 45,
              minRotation: 45,
          }
        },
        y: {
          ...cartesianOptions.scales.y,
          beginAtZero: true
        }
      }
    },
    // *** ลงทะเบียนปลั๊กอินสำหรับ Bar Chart ***
    plugins: [ChartDataLabels]
  });
})();

// Region (กราฟแท่งแนวตั้ง พร้อม Data Label)
(() => {
  const ctx = document.getElementById('chartRegion');
  if (!region.length) { ctx.parentElement.classList.add('d-none'); return; } // ซ่อนถ้าข้อมูลว่าง
  const {labels, values} = toXY(region, 'region', 'net_sales');
  new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets: [{ 
      label: 'ยอดขาย (฿)', 
      data: values,
      backgroundColor: chartColors[2] // Color 3
    }] },
    options: {
      ...cartesianOptions,
      plugins: {
        ...cartesianOptions.plugins,
        ...dataLabelPluginOptions, // เพิ่ม Data Label
      }
    },
    plugins: [ChartDataLabels]
  });
})();

// Payment
(() => {
  const ctx = document.getElementById('chartPayment');
  if (!payment.length) { ctx.parentElement.classList.add('d-none'); return; } // ซ่อนถ้าข้อมูลว่าง
  const {labels, values} = toXY(payment, 'payment_method', 'net_sales');
  new Chart(ctx, {
    type: 'pie',
    data: { labels, datasets: [{ data: values, backgroundColor: chartColors }] }, // Use color palette
    options: polarOptions
  });
})();

// Hourly (กราฟแท่งแนวตั้ง พร้อม Data Label)
(() => {
  const ctx = document.getElementById('chartHourly');
  if (!hourly.length) { ctx.parentElement.classList.add('d-none'); return; } // ซ่อนถ้าข้อมูลว่าง
  const {labels, values} = toXY(hourly, 'hour_of_day', 'net_sales');
  new Chart(ctx, {
    type: 'bar',
    data: { labels, datasets: [{ 
      label: 'ยอดขาย (฿)', 
      data: values,
      backgroundColor: chartColors[4] // Color 5
    }] },
    options: {
      ...cartesianOptions,
      plugins: {
        ...cartesianOptions.plugins,
        ...dataLabelPluginOptions, // เพิ่ม Data Label
      }
    },
    plugins: [ChartDataLabels]
  });
})();

// New vs Returning
(() => {
  const ctx = document.getElementById('chartNewReturning');
  if (!newReturning.length) { ctx.parentElement.classList.add('d-none'); return; } // ซ่อนถ้าข้อมูลว่าง
  const labels = newReturning.map(o => o.date_key);
  // *** FIX 5: เพิ่มการตรวจสอบค่า null/undefined ก่อน parseFloat ***
  const newC = newReturning.map(o => o.new_customer_sales !== null && o.new_customer_sales !== undefined ? parseFloat(o.new_customer_sales) : 0);
  const retC = newReturning.map(o => o.returning_sales !== null && o.returning_sales !== undefined ? parseFloat(o.returning_sales) : 0);
  new Chart(ctx, {
    type: 'line',
    data: { labels,
      datasets: [
        { 
          label: 'ลูกค้าใหม่ (฿)', 
          data: newC, 
          tension: .25, 
          fill: false,
          borderColor: chartColors[0] // Color 1
        },
        { 
          label: 'ลูกค้าเดิม (฿)', 
          data: retC, 
          tension: .25, 
          fill: false,
          borderColor: chartColors[3] // Color 4
        }
      ]
    },
    options: {
      ...cartesianOptions, // Import global options
      scales: {
        ...cartesianOptions.scales, // Import global scales
        x: { // Override X-axis
          ...cartesianOptions.scales.x,
          ticks: {
            ...cartesianOptions.scales.x.ticks,
            maxTicksLimit: 12 // Keep specific option
          }
        }
      }
    }
  });
})();
</script>

</body>
</html>
