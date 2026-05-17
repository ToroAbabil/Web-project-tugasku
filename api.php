<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/tasks.php';
require_once __DIR__ . '/includes/google.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ---- Add Task ----
    case 'add_task':
        $title    = trim($_POST['title']    ?? '');
        $subject  = trim($_POST['subject']  ?? '');
        $deadline = trim($_POST['deadline'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        
        if (!$title || !$deadline) {
            echo json_encode(['success'=>false,'message'=>'Judul dan deadline wajib diisi!']);
            exit;
        }
        
        $id = addTask($title, $subject, $deadline, $desc, $priority);
        echo json_encode(['success'=>true,'id'=>$id,'message'=>'Tugas berhasil ditambahkan! 💪']);
        break;

    // ---- Delete Task ----
    case 'delete_task':
        $id = $_POST['id'] ?? '';
        if (deleteTask($id)) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false,'message'=>'Tugas tidak ditemukan']);
        }
        break;

    // ---- Upload File to Drive ----
    case 'submit_task':
        $taskId = $_POST['task_id'] ?? '';
        $task   = getTask($taskId);
        
        if (!$task) {
            echo json_encode(['success'=>false,'message'=>'Tugas tidak ditemukan']);
            exit;
        }
        
        $accessToken = getValidAccessToken();
        if (!$accessToken) {
            echo json_encode(['success'=>false,'message'=>'Sesi login habis, silakan login ulang','reauth'=>true]);
            exit;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success'=>false,'message'=>'File tidak valid atau tidak ada file yang dipilih']);
            exit;
        }
        
        $file     = $_FILES['file'];
        $maxSize  = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $maxSize) {
            echo json_encode(['success'=>false,'message'=>'Ukuran file maksimal 50MB']);
            exit;
        }
        
        // Dapatkan/buat folder di Drive
        $folderId = getOrCreateFolder($accessToken, GDRIVE_FOLDER_NAME);
        if (!$folderId) {
            echo json_encode(['success'=>false,'message'=>'Gagal mengakses Google Drive']);
            exit;
        }
        
        // Nama file: [Mapel] Judul Tugas - timestamp.ext
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $task['subject'] . ' - ' . $task['title']);
        $fileName = "[{$task['subject']}] {$task['title']} - " . date('d-m-Y_His') . ".{$ext}";
        
        // Upload ke Drive
        $uploaded = uploadFileToDrive($accessToken, $file['tmp_name'], $fileName, $file['type'], $folderId);
        
        if (!isset($uploaded['id'])) {
            echo json_encode(['success'=>false,'message'=>'Gagal upload ke Google Drive']);
            exit;
        }
        
        // Update task status
        $deadlineTs   = strtotime($task['deadline']);
        $isOnTime     = time() <= $deadlineTs;
        $streakNote   = $isOnTime ? '✅ Tepat waktu! Keren!' : '⏰ Terlambat, tapi lebih baik terlambat daripada tidak dikerjakan!';
        
        updateTask($taskId, [
            'status'       => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s'),
            'drive_file'   => $uploaded['name'] ?? $fileName,
            'drive_link'   => $uploaded['webViewLink'] ?? null,
            'streak_note'  => $streakNote,
        ]);
        
        echo json_encode([
            'success'    => true,
            'on_time'    => $isOnTime,
            'drive_link' => $uploaded['webViewLink'] ?? null,
            'message'    => $isOnTime ? '🎉 Tugas dikumpulkan tepat waktu! Streak kamu bertambah!' : '📤 Tugas berhasil dikirim! Meski telat, kamu tetap keren!',
        ]);
        break;

    // ---- Get Tasks (for refresh) ----
    case 'get_tasks':
        $tasks = loadTasks();
        $stats = getStats();
        // Sort by deadline
        uasort($tasks, fn($a,$b) => strtotime($a['deadline']) - strtotime($b['deadline']));
        echo json_encode(['success'=>true,'tasks'=>array_values($tasks),'stats'=>$stats]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Action tidak dikenal']);
}
