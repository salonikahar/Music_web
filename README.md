# 🎵 Spotify Clone

A full-featured music streaming web application with user accounts, premium subscriptions, and an admin panel.

## 🚀 Easy Installation

1.  **Place the Folder**
    Copy the `Spotify-clone` folder into your server's root directory (e.g., `C:\xampp\htdocs\Spotify-clone`).

2.  **Start Servers**
    Open XAMPP Control Panel and start **Apache** and **MySQL**.

3.  **One-Click Setup**
    - Go to: `http://localhost/Spotify-clone/setup.php`
    - Click **"Install Database"**.
    - *That's it! No manual imports needed.*

4.  **Run Application**
    - User App: `http://localhost/Spotify-clone/`
    - Admin Panel: `http://localhost/Spotify-clone/admin/`

---

## 📖 Feature Manual

### 1. 👤 User Side
The main application where users listen to music.

*   **Sign Up / Sign In**:
    *   Secure account creation with username, email, and password.
    *   Passwords allow special characters (e.g. `Test@123`).
    *   Input details are remembered if you successfully submit but have an error (e.g., wrong password).
*   **Music Playback**:
    *   Browse by Albums, Artists, or Playlists.
    *   Play songs with the integrated music player (Play, Pause, Next, Previous).
*   **Premium Subscription**:
    *   Some functionality (like downloading songs) requires a Premium account.
    *   **Payment Gateway**: Integrated with Razorpay.
    *   **Cost**: ₹99/month.
    *   **Flow**: Click "Upgrade" -> Pay via Razorpay Test Mode -> Account instantly becomes Premium.

### 2. 🛡️ Admin Side
The control center for managing the platform.

*   **Access**: Go to `/admin/` and login with admin credentials.
    *   *Default Admin*: (You may need to create one manually in database or register a user and change their role if implemented, otherwise check `admin/login.php` logic).
*   **Dashboard**:
    *   View real-time statistics: Total Users, Songs, Artists, Albums.
    *   Visual charts for content distribution and growth.
*   **Content Management**:
    *   **Songs**: Add new MP3 files, assign to albums/artists, set covers.
    *   **Albums**: Create albums with specific background colors or cover images.
    *   **Artists**: Manage artist profiles and bios.
*   **User Management**: View registered users and their status.

---

## 💳 Payment Integration (Razorpay)

The project uses Razorpay for handling subscriptions.

*   **Configuration**: Keys are located in `config/razorpay.php`.
*   **Test Mode**: Currently set to Test Mode. You can use test card details to simulate payments.
*   **Verification**: Payments are verified server-side (`payment/verify_payment.php`) to ensure security before granting Premium status.

---

## 🛠️ Advanced Configuration

*   **Database Config**: `config/db.php`
*   **Base URL**: `index.php` (line 5)
*   **Uploads**: All music and images are stored in `uploads/` directory. Ensure this folder has write permissions.

---

*Made with ❤️ for Music Lovers*
