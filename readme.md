# **VIVO Application Documentation**

This document provides a comprehensive guide for setting up, running, and understanding the VIVO media management application. It is intended for new developers joining the project.

## **1\. Introduction**

VIVO is a sophisticated media management platform built with a modern tech stack. It allows users to upload various media types (videos, images, documents), which are then processed and stored securely. The application features a robust, scalable backend using Laravel and a dynamic, responsive frontend built with Vue 3, Inertia.js, and TypeScript.

### **Core Technologies**

* **Backend:** Laravel 11, Laravel Sail (Docker)  
* **Frontend:** Vue 3 (Composition API with \<script setup\>), Inertia.js, TypeScript, Tailwind CSS  
* **UI Library:** shadcn-vue  
* **Video Player:** Vidstack  
* **Cloud Services:** Amazon Web Services (AWS) for S3 storage and MediaConvert for video transcoding.

## **2\. Prerequisites**

Before you begin, ensure you have the following software and accounts set up:

* **Docker Desktop:** For running the Laravel Sail development environment.  
* **Node.js & npm:** For managing frontend dependencies.  
* **Composer:** For managing PHP dependencies.  
* **AWS Account:** With access to S3, IAM, MediaConvert, and EventBridge.  
* **AWS CLI:** (Optional but recommended) For easily retrieving your MediaConvert endpoint.  
* **Expose:** (Optional but recommended for webhook testing) A tunneling service to expose your local server to the internet.

## **3\. Local Development Setup**

### **A. Backend Setup (Laravel Sail)**

1. **Clone the Repository:**  
   git clone \<repository-url\> vivo  
   cd vivo

2. **Install Dependencies:**  
   composer install

3. **Configure Environment:**  
   * Copy the example environment file:  
     cp .env.example .env

   * Generate an application key:  
     sail artisan key:generate

4. **Start Sail:**  
   ./vendor/bin/sail up \-d

5. **Run Migrations and Seeders:** This will create your database schema and populate it with test data.  
   sail artisan migrate:fresh \--seed

6. **Create Symbolic Link:** This makes your public/storage directory accessible.  
   sail artisan storage:link

### **B. Frontend Setup**

1. **Install Node Modules:**  
   sail npm install

2. **Run the Vite Dev Server:** This will watch for changes and compile your frontend assets.  
   sail npm run dev

Your local application should now be running at http://localhost.

## **4\. AWS Integration Setup (Step-by-Step)**

This section provides a detailed guide for setting up the AWS services required for file storage and video processing.

### **A. S3 Buckets 🪣**

You need two S3 buckets: one for raw uploads and one for the final, public media.

1. **Navigate to S3** in your AWS Console.  
2. **Create the Raw Uploads Bucket:**  
   * Click **"Create bucket"**.  
   * **Bucket name:** vivo-uploads-raw (or a unique name of your choice).  
   * Keep all default settings (private access). Click **"Create bucket"**.  
3. **Create the Processed Media Bucket:**  
   * Click **"Create bucket"** again.  
   * **Bucket name:** vivo-media-processed (or a unique name).  
   * Go to the **Permissions** tab for this new bucket.  
   * Under **"Block public access"**, click **"Edit"** and uncheck all boxes to allow public access.  
   * Scroll to **"Bucket policy"** and paste the following policy, replacing vivo-media-processed with your actual bucket name:  
     {  
         "Version": "2012-10-17",  
         "Statement": \[  
             {  
                 "Sid": "PublicReadGetObject",  
                 "Effect": "Allow",  
                 "Principal": "\*",  
                 "Action": "s3:GetObject",  
                 "Resource": "arn:aws:s3:::vivo-media-processed/\*"  
             }  
         \]  
     }

### **B. IAM User & Role 🔑**

1. **Create an IAM User for Laravel:**  
   * Navigate to the **IAM** service in AWS.  
   * Go to **Users** and click **"Create user"**.  
   * **User name:** VIVO-App-User.  
   * **Permissions:** Select **"Attach policies directly"**. Search for and attach AmazonS3FullAccess and AWSElementalMediaConvertFullAccess.  
   * Create the user and then go to the **"Security credentials"** tab to create an access key. Save the **Access key ID** and **Secret access key** for your .env file.  
2. **Create an IAM Role for MediaConvert:**  
   * In IAM, go to **Roles** and click **"Create role"**.  
   * **Trusted entity type:** AWS service.  
   * **Use case:** Select **MediaConvert**.  
   * **Permissions:** Attach the AmazonS3FullAccess policy.  
   * **Role name:** VIVO-MediaConvert-Access-Role.  
   * Create the role and copy its **ARN** for your .env file.

### **C. MediaConvert Endpoint 🌐**

1. Navigate to the **AWS Elemental MediaConvert** service.  
2. In the left menu, click on **Settings**.  
3. Copy the **API endpoint** URL for your .env file.

### **D. Webhook Setup with EventBridge ⚡**

This is the automation pipeline that notifies your Laravel app when a job is complete.

1. **Navigate to the Amazon EventBridge** service.  
2. **Create an API Destination:** In the left menu, create a new API destination pointing to your application's public webhook URL (e.g., https://your-app.com/api/aws/mediaconvert). Use a tunneling service like **Expose** for local development.  
3. **Create a Rule:**  
   * **Name:** VIVO-MediaConvert-Status-Change.  
   * **Event pattern:** Select AWS services \> MediaConvert \> MediaConvert Job State Change.  
   * **Target:** Select EventBridge API destination and choose the destination you just created.

### **E. .env Configuration**

Update your .env file with all the necessary AWS credentials and endpoints. Refer to the .env setup guide in our conversation history for a full list of required variables. After updating, clear your config cache:

sail artisan config:cache

## **5\. Key Features & Architecture**

### **A. Chunked Uploads**

* **Frontend:** The useUploader.ts composable slices files into 5MB chunks. It uses the fetch API to send each chunk to a dedicated backend route.  
* **Backend:** The MediaController@uploadChunk method receives each chunk and stores it in a temporary local directory. When the final chunk arrives, it calls the assembleFile method.

### **B. File Assembly & Duplicate Detection**

* The assembleFile method combines the chunks into a single file.  
* It then calculates a **SHA256 hash** of the file's content and checks the media table to see if a file with the same hash already exists.  
* If the file is unique, it's uploaded to the vivo-uploads-raw S3 bucket.

### **C. Background Transcoding**

* After the original file is uploaded, the controller dispatches a TranscodeMediaJob.  
* This job is handled by a **queue worker** (sail artisan queue:work).  
* The job's handle() method uses the AWS SDK to create a new job in **AWS MediaConvert**, telling it to transcode the video into multiple resolutions (1080p, 720p, etc.).

### **D. Webhook Notification**

* When the MediaConvert job is complete, **AWS EventBridge** sends a notification to the /api/aws/mediaconvert webhook in your application.  
* The WebhookController@handleMediaConvert method parses this notification.  
* It creates new records in the media\_encodes table for each successfully transcoded file and updates the main media item's status to "Published."

### **E. Caption Workflow**

1. **Request:** A user can request captions for a video during upload. This creates a media\_captions record with a status of "requested."  
2. **Admin Approval:** An admin visits the /admin/caption-requests page to view pending requests.  
3. **Approval/Rejection:** The admin can approve or reject the request. This updates the status in the database and sends a notification email to the user.  
4. **Vendor Integration:** If approved, a SendTo3PlayJob is dispatched, which would (in a full implementation) send the media to a third-party captioning service.

## **6\. Running the Full Application**

To run the complete application in a development environment, you need **three terminal windows** running simultaneously:

1. **Sail Services:** ./vendor/bin/sail up  
2. **Vite Dev Server:** sail npm run dev  
3. **Queue Worker:** sail artisan queue:work

This ensures that your application is running, your frontend assets are compiled, and your background jobs are being processed.