const hubspot = require("@hubspot/api-client");
const axios = require("axios");
const qs = require("qs");

exports.main = async (event, callback) => {
  const hs_latest_source = event.inputFields["hs_latest_source"];
  let hs_object_id = event.inputFields["hs_object_id"];
  const tmp_product_interest_dynamics_landing =
    event.inputFields["tmp_product_interest_dynamics_landing"];
  const hs_latest_source_data_1 = event.inputFields["hs_latest_source_data_1"];
  let observations__dynamics_ = event.inputFields["observations__dynamics_"];

  // create modifiable variables in case they are empty
  let firstname = event.inputFields["firstname"];
  let lastname = event.inputFields["lastname"];
  const email = event.inputFields["email"];
  let phone = event.inputFields["phone"];
  let md_city = event.inputFields["md_city"];

  let numero_de_cedula = event.inputFields["numero_de_cedula"];
  let modalidad_de_estudio = event.inputFields["modalidad_de_estudio"];
  let product_id_dynamics = event.inputFields["product_id_dynamics"];
  let gd_student_type_antiquity =
    event.inputFields["gd_student_type_antiquity"];
  let campaign_attribution = event.inputFields["campaign_attribution"];
  let prefered_contact_channel = event.inputFields["prefered_contact_channel"];

  let tmp_md_first_json_sent = event.inputFields["tmp_md_first_json_sent"];
  

  // helpers
  let tmp_md_object_id = event.inputFields["md_object_id"];
  let codigo_origen = event.inputFields["codigo_origen"];
  let nombre_carrera, id_carrera, md_json_sent, products;
  // Product flag to modify Cédula de identidad, to be used in the Wizard Case
  let product_flag_modify_ci = "";

  try {
    const response = await axios.get(
      "https://eleve-products.herokuapp.com/api/getProducts"
    );
    products = response.data;
  } catch (error) {
    console.error(error);
    // Handle the error as needed
    return;
  }

  var data = qs.stringify({
    grant_type: "password",
    username: "UA",
    password: "UaPassW!",
  });
  var config = {
    method: "post",
    url: "http://190.128.233.147:8088/getToken",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    data: data,
  };

  // if codigo_origen is unavailable use the default value.
  if (!codigo_origen || codigo_origen.trim() === "") {
    codigo_origen = "OL-ODH";
  } else {
    // clean the code, remove spaces.
    codigo_origen = codigo_origen.replace(/\s/g, '');
  }
  // Check if numero_de_cedula is a number between 500000 and 9999999
  if (!numero_de_cedula || numero_de_cedula < 500000 || numero_de_cedula > 9999999) {
    numero_de_cedula = hs_object_id;
  }

  if (!tmp_md_object_id) {
    tmp_md_object_id = "";
  }
  if (firstname == "" || !firstname) {
    firstname = "Sin Nombre";
  }
  if (lastname == "" || !lastname) {
    lastname = "Sin Apellido";
  }
  if (!phone) {
    phone = "111";
  }
  if (!md_city) {
    md_city = "AS8";
  }
  if (!campaign_attribution) {
    campaign_attribution = "UNKNOWN";
  }
  if (!gd_student_type_antiquity) {
    gd_student_type_antiquity = "NUEVO";
  }

  if (product_id_dynamics) {
    for (let i = 0; i < products.length; i++) {
      if (products[i].md_id_carrera == product_id_dynamics) {
        nombre_carrera = products[i].md_nombre_carrera;
        id_carrera = products[i].md_id_carrera;

        // if the product hs_slug_code contains "wz-" then the product is a wizard product
        if (products[i].hs_slug_code.includes("wz-")) {
          product_flag_modify_ci = "WZ";
        }
      }
    }
  } else {
    if (modalidad_de_estudio && tmp_product_interest_dynamics_landing) {
      for (let i = 0; i < products.length; i++) {
        if (
          products[i].md_landing_value ==
            tmp_product_interest_dynamics_landing &&
          products[i].modality == modalidad_de_estudio
        ) {
          nombre_carrera = products[i].md_nombre_carrera;
          id_carrera = products[i].md_id_carrera;
        }
      }
    }

    if (!modalidad_de_estudio && tmp_product_interest_dynamics_landing) {
      for (let i = 0; i < products.length; i++) {
        if (
          products[i].md_landing_value == tmp_product_interest_dynamics_landing
        ) {
          nombre_carrera = products[i].md_nombre_carrera;
          id_carrera = products[i].md_id_carrera;
          modalidad_de_estudio = products[i].modality;
        }
      }
    }
  }

  // make an exception for the wizard product if flag is set to "WZ"
  // if the product is a wizard product, then the nrodocumento should be set to hs_object_id and id_hubspot viseversa
  if (product_flag_modify_ci == "WZ") {
    var temp = numero_de_cedula;
    numero_de_cedula = hs_object_id;
    hs_object_id = temp;
  }

  axios(config)
    .then(function (response) {
      let data = JSON.stringify({
        nombre: firstname,
        apellido: lastname,
        telefono: phone,
        mail: email,
        nombre_carrera: nombre_carrera,
        id_carrera: id_carrera,
        id_sede: "58440f3f-504d-ec11-b945-00505689be00",
        observaciones: observations__dynamics_,
        nrodocumento: numero_de_cedula,
        cod_universidad: "UA",
        origen: codigo_origen,
        fuente_origen: hs_latest_source,
        id_hubspot: hs_object_id,
        id_ciudad: md_city,
        new_tipodealumno: gd_student_type_antiquity,
        new_campaa: campaign_attribution,
        new_prefered_contact_channel: prefered_contact_channel,
        // "description": tmp_md_first_json_sent,
        // "fuente_complementaria":hs_latest_source_data_1,
        // "new_urldeadjuntos": tmp_md_first_json_sent,
      });
      md_json_sent = data;
      console.log(md_json_sent);

      let config = {
        method: "post",
        url: "http://190.128.233.147:8088/api/ContactOPP/",
        headers: {
          Authorization: "Bearer " + response.data.access_token,
          "Content-Type": "application/json",
        },
        data: data,
      };
      callback({
        outputFields: {
          md_json_sent: md_json_sent,
          id_carrera: id_carrera,
          md_object_id: tmp_md_object_id,
        },
      });
      axios(config)
        .then(function (response) {
          console.log(JSON.stringify(response.data));
          callback(null);
        })
        .catch(function (error) {
          console.log(error);
        });
    })
    .catch(function (error) {
      console.log(error);
    });
};