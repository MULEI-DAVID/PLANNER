# C-Planner — Family To-Do List, Finance & Household Activity Manager

A comprehensive web application designed for families to organize daily tasks, household events, finances, and side jobs in one collaborative platform.

## 🌟 Features

### 👥 User Management
- **Role-based system**: Wife, Husband, Child roles
- **Secure authentication**: OAuth2-style login system
- **User profiles**: Name, avatar, email, role management

### 📋 Task Management
- **CRUD operations**: Create, Read, Update, Delete tasks
- **Task categories**: Household, School, Groceries, Side Jobs, Events, Miscellaneous
- **Priority levels**: Low, Medium, High, Urgent
- **Status tracking**: Pending, In Progress, Completed, Cancelled
- **Recurring tasks**: Daily, weekly, monthly patterns
- **Assignment system**: Assign tasks to specific family members

### 📅 Event Management
- **Event types**: Birthday, Appointment, Bill Payment, Maintenance, Other
- **Calendar integration**: Date and time management
- **Reminder system**: Set custom reminder times
- **Status tracking**: Past, Today, Upcoming events

### 💰 Financial Management
- **Income tracking**: Salary, Side Job, Allowance, Misc
- **Expense tracking**: Groceries, Utilities, Education, Entertainment, Savings, Misc
- **Monthly summaries**: Income vs Expenses analysis
- **Balance calculation**: Real-time financial overview

### 📊 Dashboard & Analytics
- **Statistics cards**: Task completion, event counts, financial summaries
- **Recent activities**: Latest tasks, upcoming events
- **Quick actions**: Fast access to common functions
- **Visual indicators**: Color-coded status and priority badges

## 🎨 Design & UI

- **Responsive design**: Works on mobile, tablet, and desktop
- **Modern UI**: Clean, intuitive interface
- **Theme colors**: Pink and blue gradient design
- **Interactive elements**: Hover effects, animations, tooltips
- **Accessibility**: User-friendly for all family members

## 🛠️ Technical Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Authentication**: Session-based with password hashing
- **Icons**: Font Awesome 6.0
- **Charts**: Chart.js (for future analytics)

## 📁 Project Structure

```
PLANNER/
├── index.php                 # Main entry point
├── dashboard.php            # Dashboard with statistics
├── tasks.php               # Task management
├── events.php              # Event management
├── finances.php            # Financial tracking
├── auth/                   # Authentication
│   ├── login.php          # Login page
│   ├── register.php       # Registration page
│   └── logout.php         # Logout functionality
├── config/                 # Configuration
│   └── database.php       # Database connection
├── includes/               # Shared components
│   ├── header.php         # Page header
│   └── footer.php         # Page footer
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   ├── js/
│   │   └── app.js         # Main JavaScript
│   └── images/            # Images and avatars
├── database/               # Database files
│   └── schema.sql         # Database schema
└── README.md              # This file
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for future package management)

### Step 1: Clone or Download
```bash
git clone <repository-url>
cd PLANNER
```

### Step 2: Database Setup
1. Create a MySQL database named `c_planner`
2. Import the database schema:
```bash
mysql -u root -p c_planner < database/schema.sql
```

### Step 3: Configuration
1. Edit `config/database.php` with your database credentials:
```php
private $host = 'localhost';
private $db_name = 'c_planner';
private $username = 'your_username';
private $password = 'your_password';
```

### Step 4: Web Server Configuration
1. Point your web server to the project directory
2. Ensure PHP has write permissions for session management
3. Configure your web server to handle PHP files

### Step 5: Access the Application
1. Open your web browser
2. Navigate to your project URL
3. You'll be redirected to the login page

## 👤 Default Login

The system comes with a default admin account:
- **Email**: admin@cplanner.com
- **Password**: password

## 🔧 Usage Guide

### Getting Started
1. **Register**: Create accounts for all family members
2. **Login**: Use your credentials to access the dashboard
3. **Add Tasks**: Start by creating some household tasks
4. **Create Events**: Add important family events
5. **Track Finances**: Record income and expenses

### Task Management
- Create tasks with titles, descriptions, and due dates
- Assign tasks to specific family members
- Set priority levels and categories
- Mark tasks as completed when done
- Use recurring tasks for regular chores

### Event Planning
- Add events with start and end times
- Set reminders for important events
- Categorize events by type
- Assign events to family members

### Financial Tracking
- Record all income sources
- Track expenses by category
- Monitor monthly budgets
- View financial summaries

## 🔒 Security Features

- **Password hashing**: All passwords are securely hashed
- **Session management**: Secure session handling
- **SQL injection protection**: Prepared statements
- **XSS protection**: Input sanitization
- **CSRF protection**: Form token validation

## 📱 Mobile Responsiveness

The application is fully responsive and works on:
- **Mobile phones**: Optimized for small screens
- **Tablets**: Touch-friendly interface
- **Desktop**: Full-featured experience

## 🎯 Future Enhancements

- **Email notifications**: Reminder emails for tasks and events
- **File attachments**: Upload receipts and documents
- **Advanced analytics**: Detailed financial reports
- **Calendar view**: Interactive calendar interface
- **Mobile app**: Native mobile application
- **API integration**: Third-party service connections

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Review the code comments

## 🙏 Acknowledgments

- Bootstrap for the responsive framework
- Font Awesome for the icons
- Chart.js for future analytics
- The PHP community for best practices

---

**C-Planner** - Making family organization simple and efficient! 🏠✨


