<?php

declare(strict_types=1);

// Beginner Note: Run this one-time command after the database migration to add the first Administrator.
require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This setup command can only run in a terminal.\n");
    exit(1);
}
$email = strtolower(trim((string) (getenv('AIS_BOOTSTRAP_ADMIN_EMAIL') ?: '')));
$password = (string) (getenv('AIS_BOOTSTRAP_ADMIN_PASSWORD') ?: '');
if ($email !== 'admin@gmail.com') {
    fwrite(
        STDERR,
        "Set AIS_BOOTSTRAP_ADMIN_EMAIL to admin@gmail.com before running this command.\n",
    );
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(
        STDERR,
        "Set AIS_BOOTSTRAP_ADMIN_PASSWORD to the supplied temporary password (at least 8 characters).\n",
    );
    exit(1);
}

try {
    $existing = db()->prepare(
        'SELECT user_id FROM public.app_users WHERE lower(email)=lower(:email)',
    );
    $existing->execute(['email' => $email]);
    if ($existing->fetchColumn()) {
        fwrite(STDERR, "An account with that email already exists; no account was changed.\n");
        exit(1);
    }
    db_transaction(function (PDO $pdo) use ($email, $password): void {
        $stmt = $pdo->prepare(
            "INSERT INTO public.app_users(full_name,email,password_hash,role,must_change_password) VALUES (:name,:email,:hash,'Administrator',TRUE) RETURNING user_id",
        );
        $stmt->execute([
            'name' => 'Cristel Mae Dionson',
            'email' => $email,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $id = $stmt->fetchColumn();
        $pref = $pdo->prepare('INSERT INTO public.user_preferences(user_id) VALUES (:id)');
        $pref->execute(['id' => $id]);
        $audit = $pdo->prepare(
            "INSERT INTO public.audit_logs(actor_user_id,action,entity_table,record_pk,new_values) VALUES (:id,'ACCOUNT_CHANGE','app_users',:key,'{\"role\":\"Administrator\",\"initial\":true}'::jsonb)",
        );
        $audit->execute(['id' => $id, 'key' => (string) $id]);
    });
    fwrite(
        STDOUT,
        "Initial Administrator created. The first sign-in will require a password change.\n",
    );
} catch (Throwable $error) {
    error_log($error->getMessage());
    fwrite(STDERR, "Administrator setup failed. Check database configuration and server logs.\n");
    exit(1);
}
