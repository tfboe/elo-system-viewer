echo "##################################"
echo "####### ENTRYPOINT.bash ##########"
echo "##################################"

#configure git
su $USER -c "git config --global user.name \"$GIT_NAME\" && git config --global user.email \"$GIT_EMAIL\""

echo "# start vscodium as $USER"
su $USER -c "/usr/bin/codium -w --user-data-dir /userdata /workspace"
