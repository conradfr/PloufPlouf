# config valid only for current version of Capistrano
lock '3.3.5'

set :application, 'ploufplouf'
set :repo_url, 'https://github.com/conradfr/ploufplouf.git'

# Default branch is :master
# ask :branch, proc { `git rev-parse --abbrev-ref HEAD`.chomp }.call

# Default deploy_to directory is /var/www/my_app_name
set :deploy_to, "/var/www/#{fetch(:application)}"

# Default value for :scm is :git
# set :scm, :git

# Default value for :format is :pretty
# set :format, :pretty

# Default value for :log_level is :debug
# set :log_level, :debug

# Default value for :pty is false
# set :pty, true

# Default value for :linked_files is []
# set :linked_files, fetch(:linked_files, []).push('config/database.yml')
set :linked_files, fetch(:linked_files, []).push("app/config/#{fetch(:stage)}.yml")

# Default value for linked_dirs is []
# set :linked_dirs, fetch(:linked_dirs, []).push('bin', 'log', 'tmp/pids', 'tmp/cache', 'tmp/sockets', 'vendor/bundle', 'public/system')
set :linked_dirs, fetch(:linked_dirs, []).push('vendor', 'web/bower_components');

# Default value for default_env is {}
# set :default_env, { path: "/opt/ruby/bin:$PATH" }

# Default value for keep_releases is 5
set :keep_releases, 3

namespace :deploy do

  after :restart, :clear_cache do
    on roles(:web), in: :groups, limit: 3, wait: 10 do
      # Here we can do anything such as:
      # within release_path do
      #   execute :rake, 'cache:clear'
      # end
    end
  end

end

namespace :myproject do
  task :vendors do
    on roles(:app) do
      execute "curl -s http://getcomposer.org/installer | php -- --install-dir=#{release_path}"
      execute "cd #{release_path} && #{release_path}/composer.phar install --no-dev --optimize-autoloader"
    end
  end

  task :dirperm do
    on roles(:app) do
      execute "chmod -R g+wx #{release_path}/cache"
      execute "chmod -R g+wx  #{release_path}/logs"
  #  run "chmod -R 775 #{shared_path}/web/uploads"
  #  run "ln -nfs #{shared_path}/web/uploads #{release_path}/web/uploads"
    end
  end

#	task :copy do
#    on roles(:app) do |host|
#		  %w[ app/views/base.html.twig app/views/app.html.twig bower.json .bowerrc ].each do |f|
#		    upload! f , "#{release_path}/" + f
#		  end
#    end
#  end

  task :bower do
    on roles(:app) do
		  within release_path do
				execute *%w[ bower install ]
			end
    end
  end

  #task :disable do
  #  run "mkdir -p #{shared_path}/web"
  #  run "echo 'Site is on maintenance right now. Sorry.' > #{shared_path}/web/maintenance.html"
  #  run "cp #{shared_path}/web/maintenance.html #{latest_release}/web/maintenance.html"
  #end

  #task :enable do
  #  run "rm -f #{latest_release}/web/maintenance.html"
  #end

end

after "deploy:published", "myproject:vendors"
after "deploy:published", "myproject:dirperm"
after "deploy:published", "myproject:copy"
after "myproject:copy", "myproject:bower"
