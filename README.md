#  CampusConnect - Digital Campus Platform

CampusConnect is a comprehensive digital campus platform that connects students and teachers for sharing notes, asking doubts, managing events, finding study partners, and real-time messaging.

##  Features

###  Authentication & Profiles
- Secure Login/Register system
- User roles: Student, Teacher, Admin
- Profile management with avatar upload
- Points and streak system for engagement

###  Feed & Social
- Create posts (Achievements, Opportunities, Doubts, Announcements)
- Like, Comment, Save posts
- Real-time notifications
- Infinite scrolling feed

###  Smart Notes Hub
- Upload and download study materials
- Support for PDF, DOCX, JPG, PNG files
- Download counter and points system
- Filter by subject and semester

###  Doubt Forum
- Ask questions and get answers
- Like answers and mark best answer
- Teacher verification badge
- Answer points system

### Events Calendar
- View upcoming campus events
- Register for events (Students)
- Create events (Teachers only)
- Featured events section

###  Study Partner Finder
- Post study requests
- Connect with other students
- Accept/Decline connection requests
- View connections list

###  Direct Messaging
- Real-time chat between connected users
- Unread message indicators
- Conversation history

###  Notification System
- Real-time alerts for likes, comments, answers
- Unread count badge
- Notification center page

###  Dark/Light Mode
- Toggle between dark and light themes
- Persistent preference using localStorage

###  Admin Panel
- User management (change roles, delete users)
- Content moderation (delete posts, notes, doubts)
- Site statistics dashboard

##  Tech Stack

| Technology | Purpose |
|------------|---------|
| PHP (Pure) | Backend logic and server-side processing |
| MySQL | Database management |
| HTML5/CSS3 | Structure and styling |
| Bootstrap 5 | Responsive UI framework |
| jQuery & AJAX | Dynamic interactions without page reload |
| JavaScript | Client-side functionality |

## Project Structure
campusconnect/
├── actions/ # PHP scripts for AJAX operations
├── assets/ # CSS, JS, and uploaded files
│ ├── css/
│ ├── js/
│ └── uploads/
├── auth/ # Login, Register, Logout
├── config/ # Database configuration
├── includes/ # Header, Footer, Navbar, Functions
├── pages/ # All main pages
│ ├── feed.php
│ ├── notes.php
│ ├── doubts.php
│ ├── events.php
│ ├── study.php
│ ├── profile.php
│ ├── messages.php
│ ├── connections.php
│ ├── notifications.php
│ └── saved.php
└── database.sql # Database structure


## Database Tables

| Table | Description |
|-------|-------------|
| users | User accounts and profiles |
| feed_posts | Social media posts |
| comments | Post comments |
| post_likes | Like tracking |
| notes | Study materials |
| doubts | Questions asked |
| doubt_answers | Answers to doubts |
| events | Campus events |
| event_registrations | Event RSVPs |
| study_requests | Study partner requests |
| study_matches | Connection matches |
| messages | Direct messages |
| conversations | Chat conversations |
| notifications | User notifications |
| saved_items | Bookmarked content |

##  Installation Guide

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Step 1: Install XAMPP
Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)

### Step 2: Clone or Download Project
```bash
cd C:\xampp\htdocs\
git clone https://github.com/yourusername/campusconnect.git
# Or extract the downloaded zip file
