<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Audit</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-black dark:text-white">

    <header class="bg-white shadow dark:bg-zinc-900">
        <div class="container mx-auto flex items-center justify-between p-6">
            <a href="#" class="text-2xl font-semibold text-[#FF2D20]">TrackingAudit</a>
            <nav class="flex space-x-4">
                <a href="{{ route('login') }}" class="text-black dark:text-white hover:text-[#FF2D20]">Log in</a>
                <a href="{{ route('register') }}" class="text-black dark:text-white hover:text-[#FF2D20]">Register</a>
            </nav>
        </div>
    </header>

    <main class="bg-gray-50 dark:bg-black py-16">
        <div class="container mx-auto px-6 lg:px-12 text-center">
            <h1 class="text-4xl font-extrabold text-black dark:text-white">
                Have eyes on all your outbound shipments
            </h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                Track your packages easily, efficiently, and in real-time.
                Upload a CSV or enter a tracking number to start monitoring your shipments today.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="{{ route('register') }}" class="px-8 py-3 bg-[#FF2D20] text-white font-semibold rounded shadow hover:bg-[#e5261c]">
                    Get Started
                </a>
                <a href="{{ route('login') }}" class="px-8 py-3 bg-gray-100 dark:bg-gray-800 text-black dark:text-white font-semibold rounded shadow hover:bg-gray-200 dark:hover:bg-gray-700">
                    Log In
                </a>
            </div>
        </div>
    </main>

    <section class="bg-white dark:bg-zinc-900 py-16">
        <div class="container mx-auto px-6 lg:px-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="flex flex-col items-center text-center">
                <img src="/path/to/icon1.svg" alt="Easy Uploads" class="h-12 mb-4">
                <h3 class="text-xl font-bold text-black dark:text-white">Easy CSV Uploads</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Quickly upload tracking numbers in bulk with our seamless CSV uploader.
                </p>
            </div>
            <div class="flex flex-col items-center text-center">
                <img src="/path/to/icon2.svg" alt="Real-Time Tracking" class="h-12 mb-4">
                <h3 class="text-xl font-bold text-black dark:text-white">Real-Time Tracking</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Get the most up-to-date information on your shipments' progress.
                </p>
            </div>
            <div class="flex flex-col items-center text-center">
                <img src="/path/to/icon3.svg" alt="Centralized Dashboard" class="h-12 mb-4">
                <h3 class="text-xl font-bold text-black dark:text-white">Centralized Dashboard</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Manage all your tracking data in one intuitive dashboard.
                </p>
            </div>
        </div>
    </section>

    <footer class="bg-gray-100 dark:bg-zinc-900 text-center py-6">
        <p class="text-gray-600 dark:text-gray-400">
            © 2025 TrackingAudit. All rights reserved.
        </p>
    </footer>

</body>
</html>
