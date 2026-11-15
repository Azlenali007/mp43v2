<?php
declare(strict_types=1);
http_response_code(429);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>429 Too Many Requests</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#F8F9FC] text-[#151515] min-h-screen flex items-center justify-center p-4">
  <div class="max-w-md w-full bg-white border border-slate-200 rounded-2xl p-8 shadow-sm text-center space-y-4">
    <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl mx-auto flex items-center justify-center font-bold text-xl">429</div>
    <h1 class="text-xl font-bold">Too Many Requests</h1>
    <p class="text-sm text-slate-600">You have sent too many requests in a short time. Please wait a few moments before trying again.</p>
    <a href="/" class="inline-block px-5 py-2.5 rounded-xl bg-[#7138D8] text-white text-xs font-semibold hover:bg-[#5E28C2] transition">Return Home</a>
  </div>
</body>
</html>
