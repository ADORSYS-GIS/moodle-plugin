# Moodle Platform — Your Go-To Learning Solution! 🚀

Welcome to the **Moodle Platform**! Think of this as your quick guide to understanding what we're all about. By the time
you've read this, you'll know the answers to these three super important questions:

1. **What are we building?** 🤔
2. **Why are we building it?** 🎯
3. **How is the work organized?** 👷‍♀️

---

## 1. What are we building? 🤔

We're creating a super-powered version of **Moodle**, the world's most popular open-source learning platform! Moodle is
like the ultimate online classroom, used by millions globally to create amazing courses and learning communities. It's
built on **PHP** (a common web language) and kept awesome by **Moodle HQ** and a huge worldwide community.

Imagine Moodle, but easier to set up, faster, and ready for anything! Our special version is designed to be:

- **Ready-to-Go (Turnkey):** Like a pre-built house, it’s ready to use right away! 🏠
- **Cloud-Agnostic:** It works on any cloud provider (like Amazon, Google, Microsoft) or even on your own servers –
  total flexibility! ☁️
- **Easy to Develop Locally:** Developers can work on it easily on their own computers using **Docker** (think of Docker
  as a magic box that holds all the necessary tools). 🐳
- **Simple to Deploy:** We deploy it to **Kubernetes (k3s)**, which is like a smart manager for applications, making
  sure Moodle runs smoothly. We even have a custom "operator" built in **Rust** (a super-fast programming language) to
  help! ⚙️
- **Automated Setup:** We use **Terraform** to set everything up end-to-end automatically. No manual clicking needed!
  🏗️

### What makes our Moodle stand out? ✨

- **Super Fast Performance (Thanks, Rust!):** We've rebuilt some key parts using **Rust** to make Moodle run much, much
  faster. Imagine upgrading a regular car engine to a race car engine! 🏎️💨
- **Smart Kubernetes Operator:** Our special **Rust operator** handles all the tricky stuff like "blue-green" upgrades (
  updating without downtime!), automatic backups (so you never lose your work!), and automatically adjusting to handle
  more users (autoscaling). 🧠💾
- **Smooth & Automated Setup (CI/CD):** We use **GitHub Actions** to automatically check, test, build, and deploy
  Moodle. This means less manual work and fewer mistakes! ✅🔄
- **Modular Design:** Our Moodle is built like LEGOs! 🧱 Different parts (PHP plugins and Rust components) can be updated
  independently, making maintenance and new features easier to manage.

---

## 2. Why are we building it? (Project Goals) 🎯

We're building this enhanced Moodle for some very important reasons, aiming for big wins for both our team and our
customers!

- **Faster Learning for New Engineers:** We want new team members to get up to speed quickly!
    - _Our Goal:_ Onboarding new engineers in 5 days or less. ⏱️
- **Consistent & Reliable Deployments:** We want Moodle to be deployed the exact same way every time, whether it's for
  testing or for our customers.
    - _Our Goal:_ No unexpected changes reported by Terraform for two releases in a row. ✨
- **Significant Performance Boosts:** Make Moodle lightning fast!
    - _Our Goal:_ At least **30% faster page loading** for complex courses. 🚀
- **Top-Noch Reliability:** Ensure Moodle is always available when you need it.
    - _Our Goal:_ **99.9% uptime** (that's almost always on!) and recovery from any issues in less than 15 minutes. 💪
- **Flexible Deployment Options:** Make Moodle deployable in many different ways to suit various needs.
    - _Our Goal:_ By Q4 2025, deliver and prove five different ways to deploy Moodle (like serverless, pure Kubernetes,
      cloud-only, container-only, or on your own hardware). 🌍

---

### Moodle's Amazing Core Features (What it Does Best!) 📚

Moodle is packed with features designed to make online learning powerful and easy. Here’s what’s included:

- **Customizable Look & Feel:** Easily change how your Moodle site looks – add your logo, pick colors, arrange sections,
  or even create totally unique designs! 🎨
- **Secure Sign-in & Easy Enrolment:** Over 50 ways for users to sign in (like with Google, Microsoft, or your company's
  system) and smart ways to get people into the right courses at the right time.🔒
- **Multilingual Support:** Moodle speaks your language! The entire interface can be translated, and teachers can even
  offer course materials in different languages. 🗣️🌍
- **Rich Learning Activities & Resources:** A huge variety of built-in tools for learning – like **forums** for
  discussions, **quizzes** for testing, **assignments** for submissions, and resources like **pages** or **files**. This
  means endless ways to teach and learn! 📖✍️
- **Track Progress & Skills:** Keep an eye on how learners are doing with completion tracking for activities and
  courses. You can also set up custom skill frameworks and learning plans to monitor mastery. 📊
- **Flexible Grading & Assessment:** Easy-to-use gradebooks with different grading scales, smart ways to combine grades,
  and built-in tools like rubrics for fair assessment. Quizzes support tons of question types too! ✅
- **Collaboration & Communication:** Moodle makes it easy to talk and work together with **forums**, direct **messaging
  **, **announcements**, and group tools. It also integrates with video conferencing tools like **BigBlueButton** for
  live online classes! 💬🤝
- **Powerful Analytics & Reporting:** Get detailed insights into learner activity, track progress, and create custom
  reports. Dashboards help identify students who might need extra help. 📈🧐
- **Mobile App & Offline Learning:** The official Moodle mobile app lets learners access courses on their phones, get
  notifications, work offline (like drafting assignments), and even interact with H5P content! 📱
- **Thousands of Plugins:** Moodle is super flexible! Thousands of community-made plugins can add extra features for
  anything from gamification to attendance, plagiarism checking, and much more. 🔌

---

### What's New in Moodle 5.0 (Released June 30, 2025!) 🤩

Moodle 5.0 is a huge upgrade, bringing over a hundred improvements focused on making Moodle smarter, easier to use, more
accessible, and simpler to manage!

#### AI Enhancements 🤖

- **Custom AI Integration:** Connect your own AI models using the **Ollama API**!
- **Flexible AI Settings:** Set up multiple AI tools and fine-tune how each model behaves.
- **"Explain" Option for Learners:** Students can now ask Moodle to explain content on demand – instant clarity! ✨
- **AI Policy & Usage Reports:** Admins can easily see who's agreed to AI policies and track overall AI usage.

#### Content & Activity Management 📋

- **Activities Overview:** New dashboards for teachers and students to see all course activities at a glance – super
  helpful for staying organized!
- **Centralized Question Banks:** Create quizzes faster with a central place for all your questions and six powerful
  ways to filter them. 🧠

#### Accessibility & Editing ✍️

- **Longer Image Descriptions:** Up to 750 characters for alt-text means better descriptions for visually impaired
  users. 👓
- **Improved Media Editing:** Easily drag-and-drop media, use MP3s, and enjoy a more intuitive experience when editing
  content. 🎬

#### Assessment & Notifications 💯

- **Grade Penalties for Late Submissions:** Automatically apply and display penalties for late assignments right in the
  gradebook. ⏰
- **SMS Notifications for Feedback:** Notify students about their assignment feedback via text messages, including file
  details. 📲
- **New "Graded" Filter & BigBlueButton Grading:** Quickly find graded submissions and manually assign grades to
  BigBlueButton video sessions.

#### Miscellaneous Enhancements 🎉

- **Pre-created Quiz Attempts:** Boost quiz performance by creating attempts in advance.
- **Duplicate Custom Reports:** Speed up your reporting setup by duplicating existing reports.
- **User Tour Exclusions & H5P Autoplay:** Exclude user tours from specific categories and enjoy automatic H5P content
  playback in the mobile app.

With this latest release, Moodle continues to evolve into a more intelligent, accessible, and user-friendly
platform—empowering educators and learners alike.

---

### Our Rust-Powered Enhancements (The Extra Good Stuff!) 🚀

Here’s where our **Rust** magic makes Moodle even better:

- **Super-Fast Backup/Restore:** Quickly save and restore your Moodle data with a powerful new tool. ⚡
- **Smart Auto-Scaling Operator:** Automatically manages Moodle's capacity and database connections to handle more users
  or traffic. 📈
- **On-the-Fly Image Optimization with `imgproxy`:** We use `imgproxy` to instantly resize, crop, and optimize images
  stored in **Object Storage (S3/GCS/MinIO)** for every device and screen size. This means faster page loads and
  perfect-looking images without manual effort! 🖼️💨
- **Interactive Quiz Questions:** New types of quiz questions powered by **WebAssembly** for richer interactions. 📝
- **Accelerated Calculations:** Makes complex calculations (like grading) much, much faster using Rust extensions – up
  to **3 times quicker**! ➕➖✖️➗
