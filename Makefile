PHP=/usr/bin/env php
EXECUTABLE=blackhole-bot
TARGET=/usr/local/sbin/${EXECUTABLE}
CONFIG=./Config/blackhole.yml.sample
TARGETCONFIGDIR=/etc/blackhole-bot
TARGETCONFIG=${TARGETCONFIGDIR}/blackhole.yml
SERVICEDIR=/etc/systemd/system
SERVICEFILE=./config/blackhole-bot.service
TARGETSERVICEFILE=${SERVICEDIR}/blackhole-bot.service

build:
		@echo "Building..."
		@${PHP} -dphar.readonly=0 ./bin/build.php
		@echo "Done!"

install: ${EXECUTABLE}
		@echo "Installing..."
		@cp -f ${EXECUTABLE} ${TARGET}
		@chown root:root ${TARGET}
		@chmod 0755 ${TARGET}
		@mkdir -p ${TARGETCONFIGDIR}
		@cp -n ${CONFIG} ${TARGETCONFIG}
		@chmod 0644 ${TARGETCONFIG}
		@cp ${SERVICEFILE} ${TARGETSERVICEFILE}
		@chmod 0644 ${TARGETSERVICEFILE}
		@systemctl daemon-reload
		@echo "Done!"

uninstall: ${TARGET}
		@rm -rf ${TARGET}
		@rm -rf ${TARGETCONFIGDIR}
		@rm -rf ${TARGETSERVICEFILE}
		@systemctl daemon-reload
		@echo "Uninstalled..."

clean: ${EXECUTABLE}
		@rm -rf ${EXECUTABLE}
		@echo "All clean!"

default: build