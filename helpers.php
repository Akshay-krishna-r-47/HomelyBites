<?php
// helpers.php - Global helper functions

if (!function_exists('formatName')) {
    /**
     * Formats a name to Title Case with optimal spacing.
     * Example: "AKSHAY  KRISHNA   R" -> "Akshay Krishna R"
     * 
     * @param string $name
     * @return string
     */
    function formatName($name) {
        if (empty($name)) {
            return "User";
        }
        // 1. Convert to lowercase
        // 2. Trim whitespace
        // 3. Replace multiple spaces with single space
        // 4. Convert to Title Case
        return ucwords(strtolower(preg_replace('/\s+/', ' ', trim($name))));
    }
}

if (!function_exists('getAvatarInitials')) {
    /**
     * Generates 2-letter uppercase initials from a name.
     * Example: "Akshay Krishna R" -> "AK"
     *          "Akshay" -> "A"
     * 
     * @param string $name
     * @return string
     */
    function getAvatarInitials($name) {
        $cleanName = formatName($name);
        $parts = explode(' ', $cleanName);
        
        $initials = '';
        if (count($parts) > 0) {
            $initials .= strtoupper(substr($parts[0], 0, 1));
            if (count($parts) > 1) {
                $initials .= strtoupper(substr(end($parts), 0, 1));
            }
        } else {
            $initials = 'U'; // Default fallback
        }
        
        return $initials;
    }
}

if (!function_exists('getProfileImage')) {
    /**
     * Fetches the user's profile image path.
     * 
     * @param int $user_id
     * @param mysqli $conn
     * @return string|null
     */
    function getProfileImage($user_id, $conn) {
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($profile_image);
        if ($stmt->fetch() && $profile_image) {
            if (file_exists($profile_image)) {
                $stmt->close();
                return $profile_image;
            }
        }
        $stmt->close();
        return null;
    }
}
?>
