#!/bin/bash
# Webhook Deploy Script untuk cPanel
# Letakkan di: /home/adfb2574/deploy.sh
# Akses via: https://adfsystem.online/webhook-deploy.php

cd /home/adfb2574/public_html/adf_system
git pull origin main
echo "Deployed at: $(date)"
