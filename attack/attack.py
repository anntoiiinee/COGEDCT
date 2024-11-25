from pyModbusTCP.client import ModbusClient

c = ModbusClient(host="localhost", port=502, unit_id=66, auto_open=True)
c.write_multiple_coils(25, [True])