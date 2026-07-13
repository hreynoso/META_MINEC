<?php

return [
    'auth' => [
        'google_failed' => 'Could not authenticate with Google Workspace.',
        'google_domain_only' => 'Only :domain accounts are allowed to sign in.',
        'account_blocked' => 'Your account has been blocked. Please contact the administrator.',
        'session_closed_other_device' => 'Your session was closed because you signed in on another device.',
        'session_kept_other_device' => 'Not signed in: the session on the other device remains active.',
        'invalid_credentials' => 'The credentials are invalid.',
        'invalid_local_credentials' => 'Invalid credentials for local access.',
        'too_many_attempts' => 'Too many failed attempts. Please try again in :seconds seconds.',
        'idle_timeout' => 'Your session was closed due to inactivity.',
    ],

    'ai' => [
        'updated' => 'AI settings updated.',
        'not_configured' => 'The AI API is not configured. Go to Settings → Artificial Intelligence.',
    ],

    'branding' => [
        'colors_updated' => 'System colors updated.',
        'updated' => 'Visual identity updated.',
    ],

    'sso' => [
        'updated' => 'Google SSO settings updated.',
    ],

    'kpi' => [
        'created' => 'Indicator created successfully.',
        'updated' => 'Indicator updated successfully.',
        'deleted' => 'Indicator deleted successfully.',
    ],

    'mail' => [
        'updated' => 'Email settings updated.',
        'test_failed' => 'The test email could not be sent: :error',
        'test_sent' => 'Test email sent to :email.',
    ],

    'notifications' => [
        'updated' => 'Notification preferences updated.',
        'report_queued' => 'Report queued; it will be emailed shortly.',
    ],

    'role' => [
        'created' => 'Role created successfully.',
        'updated' => 'Role updated successfully.',
        'deleted' => 'Role deleted successfully.',
        'cannot_delete' => 'The :name role cannot be deleted.',
    ],

    'user' => [
        'only_admin_can_grant_admin' => 'Only a user with the Administrator role can assign the Administrator role.',
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'cannot_delete_self' => 'You cannot delete your own account.',
        'deleted' => 'User deleted successfully.',
        'never_logged_in' => 'Never',
        'password_reused' => 'You cannot reuse one of your recent passwords.',
        'cannot_block_self' => 'You cannot block your own account.',
        'blocked' => 'Account blocked successfully.',
        'unblocked' => 'Account unblocked successfully.',
    ],

    'minister' => [
        'report_failed' => 'The report could not be generated: :error',
        'report_generated' => 'Presidential report generated.',
    ],

    'memoir' => [
        'generate_failed' => 'The memoir could not be generated: :error',
    ],

    'predictive' => [
        'ai_not_configured_fallback' => 'AI not configured; showing the model recommendation. Configure it in Settings → Artificial Intelligence.',
        'ai_query_failed' => 'The AI could not be queried: :error',
    ],

    'profile' => [
        'photo_updated' => 'Profile photo updated.',
        'photo_removed' => 'Profile photo removed.',
    ],

    'security' => [
        'review_recorded' => 'Access review recorded.',
        'alerts_saved' => 'Security alert recipients updated.',
        'invalid_email' => 'The email ":email" is not valid.',
        'deps_run' => 'Dependency analysis executed.',
        'deps_schedule_saved' => 'Dependency analysis schedule updated.',
        'deps_report_sent' => 'Dependency report generated and sent to the security team.',
        'deps_report_no_recipients' => 'No recipients are configured for the security report.',
    ],

    'backup' => [
        'saved' => 'Backup settings updated.',
        'fail_dump' => 'The database dump could not be generated.',
        'fail_upload' => 'The backup could not be uploaded to provider :provider.',
        'run_ok' => 'Backup generated and uploaded successfully.',
        'run_failed' => 'The backup could not be completed. Check the settings and the log.',
        'run_queued' => 'Backup started in the background. The result will appear in the history shortly.',
        'oauth_ok' => 'Dropbox connected: refresh token saved. Backups will no longer expire.',
        'oauth_failed' => 'Could not obtain the Dropbox refresh token: :detail',
        'invalid_credentials' => 'The credentials JSON is invalid or missing fields (client_email / private_key).',
        'test_no_token' => 'The Dropbox access token is missing.',
        'test_no_gcs' => 'The Google Cloud bucket or credentials are missing.',
        'test_bad_credentials' => 'The service account credentials are not valid.',
        'test_dropbox_ok' => 'Dropbox connection successful (:account).',
        'test_dropbox_failed' => 'Could not connect to Dropbox (code :detail).',
        'test_gcs_ok' => 'Google Cloud connection successful (bucket :bucket).',
        'test_gcs_failed' => 'Could not access the Google Cloud bucket (code :detail).',
        'test_token_failed' => 'Could not obtain the Google Cloud token (code :detail).',
    ],

    'catalog' => [
        'created' => 'Value added to the catalog.',
        'updated' => 'Catalog updated.',
        'deleted' => 'Value removed from the catalog.',
        'duplicate' => 'That value already exists in the catalog.',
        'in_use' => 'Cannot delete: the value is in use by one or more institutions.',
        'code_saved' => 'Institution code format updated.',
    ],

    'institution' => [
        'created' => 'Institution created successfully.',
        'updated' => 'Institution updated successfully.',
        'deleted' => 'Institution deleted successfully.',
        'has_projects' => 'Cannot delete: the institution has linked projects. Set it to Inactive instead.',
    ],

    'project' => [
        'created' => 'Project created successfully.',
        'updated' => 'Project updated successfully.',
        'deleted' => 'Project deleted successfully.',
    ],
];
