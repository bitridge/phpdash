<?php
// Ensure user is logged in
if (!function_exists('requireLogin')) {
    require_once __DIR__ . '/../includes/auth.php';
}
requireLogin();

// Load settings
require_once __DIR__ . '/../includes/settings.php';
$settings = Settings::getInstance();

// Get the current page name for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Set default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
}

// Get application name from settings
$appName = $settings->get('app_name', 'SEO Dashboard');

// Daily Motivational Quotes
$quotes = [
    "What great thing would you attempt, if you knew you could not fail. - Robert H. Schuller",
    "It's not about money or connections. It's the willingness to outwork and outlearn everyone when it comes to your business. - Mark Cuban",
    "Success is not final; failure is not fatal; it is the courage to continue that counts. - Winston Churchill",
    "Don't stop when you're tired. Stop when you're done. - Wesley Snipes",
    "Shoot for the moon. Even if you miss, you'll land among the stars. - Norman Vincent Peale",
    "The journey of a thousand miles begins with one step. - Lao Tzu",
    "Act as if what you do makes a difference. IT DOES. - William James",
    "Always take another step. If this is to no avail take another, and yet another. One step at a time is not too difficult. - Og Mandino",
    "If you can't fly, then run, if you can't run then walk, if you can't walk then crawl, but whatever you do, you have to keep moving forward. - Martin Luther King, Jr.",
    "Much effort, much prosperity. - Euripides",
    "Your true success in life begins only when you make the commitment to become excellent at what you do. - Brian Tracy",
    "Much good work is lost for the lack of a little more. - Edward H. Harriman",
    "Your biggest failure is the thing you dreamed of contributing but didn't find the guts to do. - Seth Godin",
    "That some achieve great success, is proof to all that others can achieve it as well. - Abraham Lincoln",
    "Never let the fear of striking out get in your way. - Babe Ruth",
    "Hustle until you no longer need to introduce yourself. - Anonymous",
    "The heights by great men reached and kept, were not attained by sudden flight, but they, while their companions slept, were toiling upward in the night. - Henry Wadsworth Longfellow",
    "He who would accomplish little must sacrifice little; he who would achieve much must sacrifice much. - James Allen",
    "If you wish to be out front, then act as if you were behind. - Lao Tzu",
    "The key to success is failure. - Michael Jordan",
    "Formula for success: rise early, work hard, strike oil. - J. Paul Getty",
    "Plough deep while sluggards sleep. - Benjamin Franklin",
    "Work hard in silence and let success be your noise. - Anonymous",
    "Don't stop until you're proud. - Anonymous",
    "The path to success is to take massive, determined action. - Tony Robbins",
    "If it's important, you'll find a way. If it's not, you'll find an excuse. - Ryan Blair",
    "Men of action are favored by the goddess of good luck. - George S. Clason",
    "A somebody was once a nobody who wanted to and did. - John Burroughs",
    "Man cannot discover new oceans unless he has the courage to lose sight of the shore. - Andre Gide",
    "If you believe you can do a thing, you can do it. - Claude M. Bristol",
    "Action is the foundational key to all success. - Pablo Picasso",
    "Thought allied fearlessly to purpose becomes creative force. - James Allen",
    "Run your own race. Who cares what others are doing? The only question that matters is, am I progressing? - Robin Sharma",
    "There's not a person on my team in 16 years that has consistently beat me to the ball every play. That ain't got anything to do with talent, that's just got everything to do with effort, and nothing else. - Ray Lewis",
    "Don't watch the clock; do what it does. Keep going. - Sam Levonson",
    "Winners embrace hard work. They love the discipline of it, the trade-off they're making to win. Losers, on the other hand, see it as a punishment. And that's the difference. - Lou Holtz",
    "Push yourself, because no one else is going to do it for you. - Anonymous",
    "Life shrinks or expands in proportion to one's courage. - Anais Nin",
    "Nothing in this world is worth having or worth doing unless it means effort, pain, difficulty. - Theodore Roosevelt",
    "In this world you only get what you grab for. - Giovanni Boccaccio",
    "Success means having the courage, the determination, and the will to become the person you believe you were meant to be. - George A. Sheehan",
    "The best way to predict the future is to create it. - Peter Drucker",
    "If you have everything seems under control, you're just not going fast enough. - Mario Andretti",
    "Do the work. Everyone wants to be successful, but nobody wants to do the work. - Gary Vaynerchuk",
    "Be so good they can't ignore you. - Steve Martin",
    "Be not afraid of going slowly; be afraid only of standing still. - Chinese Proverb",
    "When we strive to become better than we are, everything around us becomes better too. - Paulo Coelho",
    "With self-discipline, most anything is possible. - Theodore Roosevelt",
    "All the so-called 'secrets of success' will not work unless you do. - Anonymous",
    "Your dreams are on the other side of your grit. - Anonymous"
];

// Get today's quote based on the day of the year (1-366)
$dayOfYear = date('z'); // 0-365
$quoteIndex = $dayOfYear % count($quotes);
$todaysQuote = $quotes[$quoteIndex];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars($appName); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Current Date/Time Bar -->
    <div class="bg-dark py-1">
        <div class="container d-flex justify-content-between align-items-center text-white">
            <small>
                <i class="bi bi-quote me-1"></i>
                <span id="daily-quote"><?php echo htmlspecialchars($todaysQuote); ?></span>
            </small>
            <small>
                <i class="bi bi-clock me-1"></i>
                <span id="current-datetime">
                    <?php 
                    echo $settings->getCurrentDateTime(); 
                    ?>
                </span>
            </small>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <?php if ($logo = $settings->get('app_logo')): ?>
                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" height="30" class="me-2">
                <?php endif; ?>
                <?php echo htmlspecialchars($appName); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <?php if (isAdmin()): ?>
                    <!-- Admin Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'customers' ? 'active' : ''; ?>" 
                           href="customers.php">
                           <i class="bi bi-building me-1"></i>
                           Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" 
                           href="projects.php">
                           <i class="bi bi-kanban me-1"></i>
                           All Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'seo_logs' ? 'active' : ''; ?>" 
                           href="seo_logs.php">
                           <i class="bi bi-journal-text me-1"></i>
                           All SEO Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>" 
                           href="users.php">
                           <i class="bi bi-people me-1"></i>
                           Users
                        </a>
                    </li>
                    <?php else: ?>
                    <!-- SEO Provider Navigation -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" 
                           href="projects.php">
                           <i class="bi bi-kanban me-1"></i>
                           My Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'seo_logs' ? 'active' : ''; ?>" 
                           href="seo_logs.php">
                           <i class="bi bi-journal-text me-1"></i>
                           SEO Logs
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" 
                           href="settings.php">
                           <i class="bi bi-gear me-1"></i>
                           Settings
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-person me-2"></i>My Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">

<style>
.navbar {
    padding: 0.8rem 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.navbar-brand {
    font-weight: 600;
    font-size: 1.3rem;
}

.nav-link {
    padding: 0.5rem 1rem !important;
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-link:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 4px;
}

.nav-link.active {
    background-color: rgba(255,255,255,0.2);
    border-radius: 4px;
}

.navbar-nav .nav-item {
    margin: 0 0.2rem;
}

.bi {
    font-size: 1.1rem;
    vertical-align: -2px;
}
</style>
</body>
</html> 