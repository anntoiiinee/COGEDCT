FROM debian
ENV TERM linux
RUN apt-get update && apt-get -y upgrade && apt-get -y install dialog apt-utils tcpdump
RUN apt-get -y install python3 python3-pip apache2 php libapache2-mod-php git
RUN mv /usr/lib/python3.11/EXTERNALLY-MANAGED /usr/lib/python3.11/EXTERNALLY-MANAGED.old
RUN pip3 install pymodbus@git+https://github.com/haxom/pymodbus.git --user
COPY . /data
WORKDIR /data
RUN cp -r /data/web/* /var/www/html && chown -R www-data:www-data /var/www/html/ && rm /var/www/html/index.html

RUN echo SetEnv OPERATOR_PWD operator >> /etc/apache2/conf-enabled/environment.conf

EXPOSE 80/tcp
EXPOSE 502/tcp

CMD service apache2 restart && python3 /data/process/wind.py &> /dev/null & python3 /data/process/eolienne_server.py &> /dev/null & python3 /data/process/eolienne_process.py &> /dev/null & tail -f /dev/null
