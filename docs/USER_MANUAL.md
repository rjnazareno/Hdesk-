# User Manual
## IT Ticket Management System

**Version:** 1.0  
**Target Audience:** End Users (Employees and IT Staff)  
**Last Updated:** September 26, 2025  

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Employee Guide](#employee-guide)
3. [IT Staff Guide](#it-staff-guide)
4. [Common Tasks](#common-tasks)
5. [Troubleshooting](#troubleshooting)
6. [FAQ](#faq)

---

## Getting Started

### System Overview

The IT Ticket Management System helps you request technical support and track the progress of your IT issues. The system is designed to be simple and efficient, allowing you to focus on your work while IT staff resolve your technical problems.

### Accessing the System

1. **Open your web browser** (Chrome, Firefox, Safari, or Edge)
2. **Navigate to:** `http://your-company-domain/ticketing-system/simple_login.php`
3. **Enter your credentials** provided by your IT administrator
4. **Click "Login"** to access the system

### User Roles

- **Employee**: Can create tickets, view their own tickets, and communicate with IT staff
- **IT Staff**: Can view all tickets, assign tickets, update status, and manage resolutions

---

## Employee Guide

### Creating Your First Ticket

1. **Access the Dashboard**
   - After logging in, you'll see your dashboard with any existing tickets

2. **Create a New Ticket**
   - Click the **"+ New Ticket"** button
   - Fill in the required information:
     - **Subject**: Brief description of your issue (e.g., "Computer won't start")
     - **Description**: Detailed explanation of the problem
     - **Category**: Choose the most appropriate category:
       - Hardware (computer, printer, mouse, etc.)
       - Software (applications, programs, updates)
       - Network (internet, wifi, email issues)
       - Security (password, access, suspicious activity)
       - Other (anything that doesn't fit above)
     - **Priority**: Select based on urgency:
       - **Low**: Minor issues that don't affect work
       - **Medium**: Issues that slow down work
       - **High**: Issues that prevent normal work
       - **Urgent**: Critical issues affecting multiple people

3. **Submit the Ticket**
   - Click **"Create Ticket"**
   - You'll receive a confirmation with your ticket number

### Tracking Your Tickets

#### Dashboard View
Your dashboard shows:
- **Ticket Summary**: Quick overview of your active tickets
- **Recent Tickets**: List of your most recent tickets
- **Status Icons**: Visual indicators of ticket status
  - 🔴 **Open**: New ticket, not yet assigned
  - 🟡 **In Progress**: IT staff is working on it
  - 🟢 **Resolved**: Solution provided, awaiting your confirmation
  - ⚫ **Closed**: Issue fully resolved and confirmed

#### Viewing Ticket Details
1. **Click on any ticket** from your dashboard
2. **Review the information**:
   - Original ticket details
   - Current status and assigned IT staff member
   - All responses and updates
   - Timeline of actions taken

### Communicating with IT Staff

#### Viewing Responses
- IT staff responses appear in the **"Responses"** section
- Each response shows:
  - Staff member name
  - Date and time
  - Message content

#### Adding Your Response
1. **Scroll to the bottom** of the ticket view
2. **Type your message** in the response box
3. **Provide additional information** such as:
   - Whether the problem still exists
   - New symptoms you've noticed
   - Steps you've already tried
4. **Click "Add Response"** to send

### Best Practices for Employees

#### Creating Effective Tickets
✅ **Do:**
- Be specific about the problem
- Include error messages exactly as they appear
- Mention when the problem started
- List what you were doing when it occurred
- Include your computer name/location if relevant

❌ **Don't:**
- Use vague descriptions like "computer is broken"
- Create multiple tickets for the same issue
- Mark everything as "urgent" unless it truly is
- Include sensitive information like passwords

#### Example Good Ticket
```
Subject: Excel crashes when opening large files
Category: Software
Priority: Medium

Description: 
Every time I try to open Excel files larger than 5MB, 
the program crashes with error "Excel has stopped working". 
This started yesterday after the Windows update. 
I can open smaller files without problems.

Computer: DESK-123 in Marketing Department
```

---

## IT Staff Guide

### Dashboard Overview

The IT staff dashboard provides a comprehensive view of all tickets:

#### Statistics Cards
- **Total Open Tickets**: Unassigned new tickets
- **In Progress**: Tickets currently being worked on
- **Resolved**: Tickets awaiting employee confirmation
- **Closed**: Fully completed tickets
- **My Assigned**: Tickets assigned specifically to you

#### Filtering Options
- **Status Filter**: Show only tickets with specific status
- **Priority Filter**: Focus on high-priority items
- **Category Filter**: View tickets by problem type
- **Search**: Find tickets by keywords

### Managing Tickets

#### Viewing Ticket Details
1. **Click on any ticket** from the dashboard
2. **Review complete information**:
   - Employee details and contact information
   - Problem description and category
   - Current status and assignment
   - All communication history

#### Updating Ticket Status
1. **Select new status** from the dropdown menu:
   - **Open**: Initial state, unassigned
   - **In Progress**: Currently being worked on
   - **Resolved**: Solution provided, awaiting confirmation
   - **Closed**: Completely finished
2. **Click "Update"** to save changes

#### Assigning Tickets
1. **Choose staff member** from the "Assign to" dropdown
2. **Click "Assign"** to transfer responsibility
3. **Add a note** explaining why you're reassigning (optional)

#### Adding Responses
1. **Scroll to the response section**
2. **Type your response** with:
   - Clear explanation of the solution
   - Steps the employee should take
   - Expected timeline for resolution
3. **Choose response type**:
   - **Public**: Employee can see this message
   - **Internal Note**: Only IT staff can see this
4. **Click "Add Response"**

### Advanced Features

#### Bulk Operations
- **Filter tickets** using the dashboard controls
- **Select multiple tickets** using checkboxes (if available)
- **Apply actions** to all selected tickets

#### Reporting
- **Dashboard statistics** provide quick metrics
- **Use filters** to analyze specific time periods
- **Export data** for detailed reporting

### Best Practices for IT Staff

#### Effective Communication
✅ **Do:**
- Acknowledge tickets promptly
- Explain technical solutions in simple terms
- Set realistic expectations for resolution time
- Follow up to ensure problems are resolved
- Use internal notes for technical details

❌ **Don't:**
- Leave tickets unacknowledged for more than 2 hours
- Use technical jargon without explanation
- Promise unrealistic timelines
- Forget to update ticket status

#### Example Professional Response
```
Hi Sarah,

I've reviewed your Excel crashing issue. This appears to be 
related to the recent Windows update affecting Office 2019.

I'm going to:
1. Install the latest Office updates on your computer
2. Reset your Excel preferences
3. Test with your problematic files

This should take about 30 minutes. I'll remote into your 
computer at 2:00 PM if that works for you.

Best regards,
John - IT Support
```

---

## Common Tasks

### For Employees

#### Checking Ticket Status
1. Log into the system
2. View your dashboard for quick status overview
3. Click any ticket for detailed information

#### Following Up on a Ticket
1. Open the specific ticket
2. Add a response with new information
3. Be patient - IT staff will respond within business hours

#### Closing a Resolved Ticket
1. Verify the problem is actually fixed
2. Open the ticket
3. Add a response confirming resolution
4. IT staff will close the ticket

### For IT Staff

#### Daily Workflow
1. **Log in and check dashboard statistics**
2. **Review new tickets** (status: Open)
3. **Assign tickets** to appropriate staff
4. **Update progress** on assigned tickets
5. **Respond to employee questions**
6. **Close resolved tickets**

#### Escalating Complex Issues
1. **Add internal note** explaining the complexity
2. **Assign to senior technician** or manager
3. **Update employee** about the escalation
4. **Monitor progress** and assist as needed

---

## Troubleshooting

### Login Issues

**Problem**: Can't log into the system
**Solutions**:
- Check your username and password for typos
- Ensure Caps Lock is off
- Clear your browser cookies and cache
- Contact IT administrator for password reset

**Problem**: "Session expired" message
**Solutions**:
- Log out completely and log back in
- Close all browser windows and restart
- Check system time - ensure it's correct

### System Performance

**Problem**: Pages load slowly
**Solutions**:
- Check your internet connection
- Close unnecessary browser tabs
- Clear browser cache
- Contact IT if problem persists

**Problem**: Forms don't submit
**Solutions**:
- Ensure all required fields are filled
- Check for error messages at the top of the page
- Try refreshing the page and resubmitting
- Contact IT support if the issue continues

---

## FAQ

### General Questions

**Q: How do I get access to the ticketing system?**
A: Contact your IT administrator to create an account for you. You'll receive login credentials via email.

**Q: Can I create tickets for other people?**
A: No, employees can only create tickets for themselves. Each person needs their own account.

**Q: How long does it take to get a response?**
A: IT staff typically acknowledge tickets within 2 hours during business hours. Resolution time depends on the complexity of the issue.

**Q: Can I call instead of creating a ticket?**
A: For urgent issues, you can call. However, a ticket will still need to be created for tracking purposes.

### For Employees

**Q: I created a ticket by mistake. Can I delete it?**
A: Contact IT staff through the ticket or by phone. They can help resolve or close the ticket appropriately.

**Q: My problem is fixed but the ticket is still open. What should I do?**
A: Add a response to the ticket confirming that the problem is resolved. IT staff will then close the ticket.

**Q: Can I see tickets created by my colleagues?**
A: No, for privacy reasons, you can only see your own tickets.

**Q: What if my issue is really urgent?**
A: Set the priority to "Urgent" and also call the IT help desk number for immediate assistance.

### For IT Staff

**Q: Can I modify a ticket created by an employee?**
A: Yes, you can update status, assignment, and add responses. However, maintain the original employee description for reference.

**Q: How do I handle tickets that require external vendor support?**
A: Update the ticket status to "In Progress" and add internal notes about vendor contact. Keep the employee informed of progress.

**Q: What's the difference between "Resolved" and "Closed"?**
A: "Resolved" means you've provided a solution but need employee confirmation. "Closed" means the issue is completely finished.

**Q: Can I reopen a closed ticket?**
A: Yes, if needed. Change the status back to "Open" or "In Progress" and add a note explaining why.

---

## Getting Help

### For System Issues
- **Technical problems with the ticketing system**: Create a ticket or call IT support
- **Login problems**: Contact your system administrator
- **Feature requests**: Discuss with your IT manager

### Contact Information
- **IT Help Desk**: [Your help desk phone number]
- **Email Support**: [Your IT support email]
- **Emergency Contact**: [After-hours emergency number]

### Training Resources
- **New User Training**: Contact your IT manager to schedule
- **Video Tutorials**: Available on the company intranet
- **Quick Reference Cards**: Available for download from IT department

---

**Remember**: The ticketing system is here to help make IT support more efficient. Don't hesitate to use it for any technology-related issues, no matter how small they may seem.

**Document Version:** 1.0  
**Last Updated:** September 26, 2025  
**Next Review Date:** December 26, 2025