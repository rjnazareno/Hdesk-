# Uploads Directory

This directory stores user-uploaded files attached to tickets.

## Important Notes

- This folder must have **write permissions** for the web server
- Files are uploaded when creating or updating tickets
- Maximum file size: 5MB (configurable in `config/config.php`)
- Allowed file types: jpg, jpeg, png, pdf, doc, docx, xlsx, txt

## Security

- Direct directory listing is disabled via `.htaccess`
- File type validation is performed server-side
- Files are renamed with timestamp to prevent conflicts
- Consider moving outside web root in production

## Backup

Remember to include this directory in your backup strategy as it contains user data.

## Production Deployment

For production environments, consider:
- Moving uploads outside the web root
- Using cloud storage (AWS S3, Azure Blob, etc.)
- Implementing CDN for faster file delivery
- Regular cleanup of old attachments
