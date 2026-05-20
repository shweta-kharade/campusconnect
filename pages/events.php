<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$page_title = 'Events';
$current_user = currentUser($pdo);

// Handle filters
$event_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$view = isset($_GET['view']) ? $_GET['view'] : 'grid';

// Build query - FIXED VERSION
if ($event_type != 'all') {
    $sql = "SELECT e.*, u.name as organizer_name 
            FROM events e
            JOIN users u ON e.user_id = u.id
            WHERE e.event_type = ? AND (e.event_date >= CURDATE() OR e.status = 'upcoming')
            ORDER BY e.is_featured DESC, e.event_date ASC";
    $params = [$event_type];
} else {
    $sql = "SELECT e.*, u.name as organizer_name 
            FROM events e
            JOIN users u ON e.user_id = u.id
            WHERE e.event_date >= CURDATE() OR e.status = 'upcoming'
            ORDER BY e.is_featured DESC, e.event_date ASC";
    $params = [];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get featured event - FIXED VERSION
$sql_featured = "SELECT e.*, u.name as organizer_name,
                    (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registered
                 FROM events e
                 JOIN users u ON e.user_id = u.id
                 WHERE e.is_featured = 1 AND e.event_date >= CURDATE()
                 ORDER BY e.event_date ASC
                 LIMIT 1";
$stmt = $pdo->prepare($sql_featured);
$stmt->execute();
$featured_event = $stmt->fetch();

// Get user's registered events
$stmt = $pdo->prepare("SELECT event_id FROM event_registrations WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$registered_events = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Display messages
if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
include '../includes/header.php';
include '../includes/navbar.php';
?>

<style>
    :root {
        --primary: #4361ee;
        --secondary: #7209b7;
        --success: #06d6a0;
        --warning: #ffd166;
        --danger: #ef476f;
        --dark: #1a1a2e;
    }

    .events-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .events-header::before {
        content: "📅";
        position: absolute;
        right: -20px;
        bottom: -20px;
        font-size: 150px;
        opacity: 0.1;
    }

    /* Featured Event Card */
    .featured-event {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s;
    }

    .featured-event:hover {
        transform: translateY(-5px);
    }

    .featured-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.8rem;
    }

    /* Event Card */
    .event-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        height: 100%;
        position: relative;
    }

    .event-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }

    .event-date {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 0.75rem;
        text-align: center;
        min-width: 80px;
    }

    .event-date .day {
        font-size: 1.8rem;
        font-weight: bold;
        line-height: 1;
    }

    .event-date .month {
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    .event-type-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 500;
        z-index: 1;
    }

    .type-workshop {
        background: #4361ee;
        color: white;
    }

    .type-hackathon {
        background: #ef476f;
        color: white;
    }

    .type-exam {
        background: #ffd166;
        color: #333;
    }

    .type-seminar {
        background: #06d6a0;
        color: white;
    }

    .type-deadline {
        background: #f77f00;
        color: white;
    }

    .type-social {
        background: #7209b7;
        color: white;
    }

    .register-btn {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1.25rem;
        transition: all 0.3s;
        width: 100%;
    }

    .register-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        color: white;
    }

    .register-btn.registered {
        background: #28a745;
    }

    .filter-btn {
        border-radius: 50px;
        padding: 0.5rem 1.25rem;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-block;
    }

    .filter-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
    }

    .view-btn {
        background: none;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 0.5rem;
        transition: all 0.2s;
    }

    .view-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .countdown {
        font-size: 0.8rem;
        color: var(--danger);
        font-weight: 500;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .event-card {
        animation: slideUp 0.4s ease;
    }

    .btn-outline-danger {
        background: transparent;
        border: 1px solid #dc3545;
        color: #dc3545;
        transition: all 0.2s;
    }

    .btn-outline-danger:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
    }
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="events-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="bi bi-calendar-event-fill"></i> Campus Events
                </h1>
                <p class="mb-0 opacity-75">
                    Stay updated with workshops, hackathons, exams, and campus activities
                </p>
            </div>
            <?php if ($_SESSION['user_role'] == 'teacher'): ?>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-light px-4" data-bs-toggle="modal" data-bs-target="#createEventModal" style="border-radius: 50px;">
                        <i class="bi bi-plus-circle"></i> Create Event
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Featured Event -->
    <?php if ($featured_event): ?>
        <div class="featured-event" id="event-<?php echo $featured_event['id']; ?>">
            <div class="featured-badge">
                <i class="bi bi-star-fill"></i> Featured Event
                <?php if ($_SESSION['user_id'] == $featured_event['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                    <button class="btn btn-sm btn-link text-white"
                        onclick="deleteEvent(<?php echo $featured_event['id']; ?>, <?php echo $featured_event['user_id']; ?>)"
                        style="margin-left: 10px;">
                        <i class="bi bi-trash3"></i>
                    </button>
                <?php endif; ?>
            </div>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2"><?php echo htmlspecialchars($featured_event['title']); ?></h2>
                    <p class="mb-3 opacity-90"><?php echo htmlspecialchars(substr($featured_event['description'], 0, 200)); ?></p>
                    <div class="d-flex flex-wrap gap-3">
                        <span><i class="bi bi-calendar"></i> <?php echo date('F j, Y', strtotime($featured_event['event_date'])); ?></span>
                        <span><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($featured_event['event_time'])); ?></span>
                        <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($featured_event['venue']); ?></span>
                        <span><i class="bi bi-people"></i> <?php echo $featured_event['registered']; ?> registered</span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-light register-event-btn" data-event-id="<?php echo $featured_event['id']; ?>"
                        <?php echo in_array($featured_event['id'], $registered_events) ? 'disabled' : ''; ?>>
                        <?php echo in_array($featured_event['id'], $registered_events) ? '✓ Registered' : 'Register Now'; ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div class="d-flex flex-wrap gap-2">
            <a href="?type=all&view=<?php echo $view; ?>" class="filter-btn <?php echo $event_type == 'all' ? 'active' : 'btn-outline-secondary'; ?>">
                All Events
            </a>
            <a href="?type=workshop&view=<?php echo $view; ?>" class="filter-btn <?php echo $event_type == 'workshop' ? 'active' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-laptop"></i> Workshops
            </a>
            <a href="?type=hackathon&view=<?php echo $view; ?>" class="filter-btn <?php echo $event_type == 'hackathon' ? 'active' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-code-square"></i> Hackathons
            </a>
            <a href="?type=exam&view=<?php echo $view; ?>" class="filter-btn <?php echo $event_type == 'exam' ? 'active' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-journal-check"></i> Exams
            </a>
            <a href="?type=seminar&view=<?php echo $view; ?>" class="filter-btn <?php echo $event_type == 'seminar' ? 'active' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-mic"></i> Seminars
            </a>
            <a href="?type=deadline&view=<?php echo $view; ?>" class="filter-btn <?php echo $event_type == 'deadline' ? 'active' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-hourglass-split"></i> Deadlines
            </a>
            <a href="?type=social&view=<?php echo $view; ?>" class="filter-btn <?php echo $event_type == 'social' ? 'active' : 'btn-outline-secondary'; ?>">
                <i class="bi bi-people-fill"></i> Social
            </a>
        </div>

        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <button class="view-btn <?php echo $view == 'grid' ? 'active' : ''; ?>" onclick="changeView('grid')">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </button>
            <button class="view-btn <?php echo $view == 'list' ? 'active' : ''; ?>" onclick="changeView('list')">
                <i class="bi bi-list-ul"></i>
            </button>
        </div>
    </div>

    <!-- Events Grid/List View -->
    <?php if (empty($events)): ?>
        <div class="text-center py-5 bg-white rounded-4">
            <i class="bi bi-calendar-x" style="font-size: 4rem; color: #ccc;"></i>
            <h5 class="mt-3">No events found</h5>
            <p class="text-muted">Check back later for upcoming events!</p>
            <?php if ($_SESSION['user_role'] == 'teacher'): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                    Create First Event
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div id="events-container" class="<?php echo $view == 'grid' ? 'row g-4' : ''; ?>">
            <?php foreach ($events as $event):
                $isRegistered = in_array($event['id'], $registered_events);
                $daysLeft = ceil((strtotime($event['event_date']) - time()) / 86400);
                $event_type_class = 'type-' . $event['event_type'];
            ?>
                <?php if ($view == 'grid'): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="event-card" id="event-<?php echo $event['id']; ?>">
                            <div class="event-type-badge <?php echo $event_type_class; ?>">
                                <?php echo ucfirst($event['event_type']); ?>
                            </div>
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="event-date rounded-3 text-center">
                                        <div class="day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                        <div class="month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if ($event['is_featured']): ?>
                                            <i class="bi bi-star-fill text-warning" style="font-size: 1.2rem;"></i>
                                        <?php endif; ?>
                                        <!-- Delete Button -->
                                        <?php if ($_SESSION['user_id'] == $event['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                                            <button class="btn btn-sm btn-link text-danger"
                                                onclick="deleteEvent(<?php echo $event['id']; ?>, <?php echo $event['user_id']; ?>)"
                                                title="Delete event">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <h5 class="mb-2 mt-2"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($event['description'], 0, 80)); ?>...
                                </p>

                                <div class="mb-2">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['venue']); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-person"></i> By <?php echo htmlspecialchars($event['organizer_name']); ?>
                                    </small>
                                </div>

                                <?php if ($daysLeft <= 7 && $daysLeft > 0): ?>
                                    <div class="countdown mb-2">
                                        🔥 <?php echo $daysLeft; ?> days left
                                    </div>
                                <?php endif; ?>

                                <button class="register-btn <?php echo $isRegistered ? 'registered' : ''; ?> mt-2 register-event-btn"
                                    data-event-id="<?php echo $event['id']; ?>"
                                    <?php echo $isRegistered ? 'disabled' : ''; ?>>
                                    <?php echo $isRegistered ? '<i class="bi bi-check-circle"></i> Registered' : '<i class="bi bi-calendar-check"></i> Register'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- List View -->
                    <div class="event-card mb-3 p-3" id="event-<?php echo $event['id']; ?>">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="event-date rounded-3 text-center d-inline-block">
                                    <div class="day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                    <div class="month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                <div class="d-flex flex-wrap gap-2 small">
                                    <span class="badge <?php echo $event_type_class; ?>"><?php echo ucfirst($event['event_type']); ?></span>
                                    <span><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                    <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">By <?php echo htmlspecialchars($event['organizer_name']); ?></small>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button class="btn btn-sm <?php echo $isRegistered ? 'btn-success' : 'btn-primary'; ?> register-event-btn rounded-pill"
                                        data-event-id="<?php echo $event['id']; ?>"
                                        <?php echo $isRegistered ? 'disabled' : ''; ?>>
                                        <?php echo $isRegistered ? 'Registered' : 'Register'; ?>
                                    </button>
                                    <?php if ($_SESSION['user_id'] == $event['user_id'] || $_SESSION['user_role'] == 'teacher'): ?>
                                        <button class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="deleteEvent(<?php echo $event['id']; ?>, <?php echo $event['user_id']; ?>)"
                                            title="Delete event">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Event Modal -->
<?php if ($_SESSION['user_role'] == 'teacher'): ?>
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <form method="POST" action="../actions/create_event.php">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-plus"></i> Create New Event
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Event Title *</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g., Annual Tech Fest 2024" style="border-radius: 12px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" rows="4" class="form-control" required placeholder="Describe the event..." style="border-radius: 12px;"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Event Type</label>
                            <select name="event_type" class="form-select" style="border-radius: 12px;">
                                <option value="workshop">Workshop</option>
                                <option value="hackathon">Hackathon</option>
                                <option value="exam">Exam</option>
                                <option value="seminar">Seminar</option>
                                <option value="deadline">Deadline</option>
                                <option value="social">Social Event</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control" placeholder="e.g., Main Auditorium, Online" style="border-radius: 12px;">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Event Date *</label>
                            <input type="date" name="event_date" class="form-control" required style="border-radius: 12px;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Event Time</label>
                            <input type="time" name="event_time" class="form-control" style="border-radius: 12px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_featured" class="form-check-input" value="1" id="featuredCheck">
                            <label class="form-check-label" for="featuredCheck">
                                Feature this event (appears at top)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #4361ee, #7209b7); color: white; border-radius: 50px;">
                        <i class="bi bi-calendar-check"></i> Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    function changeView(view) {
        let currentType = '<?php echo $event_type; ?>';
        window.location.href = '?type=' + currentType + '&view=' + view;
    }

    // Register for event
    $(document).on('click', '.register-event-btn', function() {
        let btn = $(this);
        let eventId = btn.data('event-id');

        if (btn.prop('disabled') || btn.hasClass('registered')) {
            return;
        }

        $.ajax({
            url: '../actions/register_event.php',
            method: 'POST',
            data: { event_id: eventId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function() {
                showToast('Error registering for event', 'error');
            }
        });
    });

    function showToast(message, type) {
        let bgColor = type === 'success' ? '#28a745' : '#dc3545';
        let toast = $(`
            <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;
                        background: ${bgColor}; color: white; padding: 12px 24px;
                        border-radius: 8px; animation: fadeIn 0.3s; z-index: 10000;">
                ${message}
            </div>
        `);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(() => toast.remove()), 3000);
    }

    // Delete event function
    function deleteEvent(eventId, eventOwnerId) {
        if (confirm('Are you sure you want to delete this event? All registrations will also be removed. This action cannot be undone!')) {
            $.ajax({
                url: '../actions/delete_event.php',
                method: 'POST',
                data: {
                    event_id: eventId,
                    event_owner_id: eventOwnerId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Event deleted successfully', 'success');
                        $(`#event-${eventId}`).fadeOut(500, function() {
                            $(this).remove();
                            if ($('.event-card').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        showToast(response.message || 'Error deleting event', 'error');
                    }
                },
                error: function() {
                    showToast('Error deleting event', 'error');
                }
            });
        }
    }
</script>

<?php include '../includes/footer.php'; ?>